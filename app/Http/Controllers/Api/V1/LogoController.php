<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneratedLogoResource;
use App\Models\GeneratedLogo;
use App\Models\GeneratedLogo as Logo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LogoController extends Controller
{
    public function show(Logo $logo): JsonResponse
    {
        $logo->load(['font', 'symbolAsset', 'generation']);

        return response()->json(['data' => (new GeneratedLogoResource($logo))->resolve()]);
    }

    public function similar(Logo $logo, Request $request): JsonResponse
    {
        $limit = min(12, (int) $request->query('limit', 6));

        $logos = GeneratedLogo::query()
            ->with(['font', 'symbolAsset'])
            ->where('generation_id', $logo->generation_id)
            ->whereKeyNot($logo->id)
            ->when($logo->style_slug, fn ($query) => $query->where('style_slug', $logo->style_slug))
            ->orderByRaw('ABS(score - ?) ASC', [$logo->score])
            ->limit($limit)
            ->get();

        if ($logos->count() < $limit) {
            $logos = GeneratedLogo::query()
                ->with(['font', 'symbolAsset'])
                ->where('generation_id', $logo->generation_id)
                ->whereKeyNot($logo->id)
                ->orderByRaw('ABS(score - ?) ASC', [$logo->score])
                ->limit($limit)
                ->get();
        }

        return response()->json([
            'data' => GeneratedLogoResource::collection($logos)->resolve(),
        ]);
    }

    /**
     * Cached, versionable SVG preview. The gallery uses this for progressive
     * rendering and social/mockup compositing without shipping design JSON.
     */
    public function preview(Logo $logo, Request $request): Response
    {
        $etag = '"'.md5($logo->id.'|'.$logo->updated_at?->timestamp.'|'.$logo->svg).'"';

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304, ['ETag' => $etag]);
        }

        return response($logo->svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'ETag' => $etag,
        ]);
    }
}
