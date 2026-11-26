<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LogoTemplate;
use App\Services\Logo\LogoComposer;
use App\Services\Logo\SvgRenderingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class RenderController extends Controller
{
    public function __construct(
        private readonly SvgRenderingService $renderer,
        private readonly LogoComposer $composer,
    ) {
    }

    /**
     * Cached SVG render of a template, optionally re-laid out for a brand name.
     * Used by SEO landing pages and template galleries.
     */
    public function template(Request $request, LogoTemplate $template): Response
    {
        $name = $request->query('name');

        if (is_string($name) && trim($name) !== '') {
            $name = mb_substr(trim(preg_replace('/[<>"&]/', '', $name) ?? ''), 0, 60);
            $key = 'render:template:'.$template->id.':'.$template->version.':'.md5($name);

            $svg = Cache::remember($key, now()->addDay(), function () use ($template, $name) {
                $template->loadMissing(['font', 'symbol']);

                if ($template->font === null) {
                    return $template->svg;
                }

                $design = $this->composer->compose([
                    'brand_name' => $name,
                    'symbol' => $template->symbol,
                    'font' => $template->font,
                    'palette' => new \App\Models\ColorPalette([
                        'name' => 'Template',
                        'slug' => 'template',
                        'colors' => $template->design_json['meta']['palette'] ?? ['#111827', '#2563EB', '#06B6D4'],
                        'is_dark' => false,
                    ]),
                    'layout' => $template->layout,
                    'style' => $template->design_json['meta']['style'] ?? 'modern',
                ]);

                return $this->renderer->sanitize($this->renderer->render($design, ['crop' => true]));
            });
        } else {
            $svg = $template->svg ?? '';
        }

        $etag = '"'.md5($template->id.'|'.$template->version.'|'.$svg).'"';

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304, ['ETag' => $etag]);
        }

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400',
            'ETag' => $etag,
        ]);
    }
}
