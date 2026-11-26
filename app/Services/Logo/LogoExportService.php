<?php

namespace App\Services\Logo;

use App\Models\Download;
use App\Models\Font;
use App\Models\LogoAsset;
use App\Models\UserDesign;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

class LogoExportService
{
    public function __construct(
        private readonly SvgRenderingService $renderer,
        private readonly ChromeRasterizer $rasterizer,
        private readonly LogoComposer $composer,
    ) {
    }

    /**
     * @return array{bytes: string, filename: string, mime: string}
     */
    public function export(UserDesign $design, string $format, string $variant = 'full', string $quality = 'high'): array
    {
        $design = $design->fresh();
        $document = $this->variantDesign($design, $variant);
        $svg = $this->renderer->sanitize($this->renderer->render(
            $document,
            ['transparent' => in_array($variant, ['transparent', 'icon'], true)]
        ));

        $slug = Str::slug($design->business_name) ?: 'logo';
        $families = $this->fontFamilies($document);
        $canvas = is_array($document['canvas'] ?? null) ? $document['canvas'] : [];
        $canvasW = max(64, (int) ($canvas['width'] ?? DesignDocument::CANVAS_WIDTH));
        $canvasH = max(64, (int) ($canvas['height'] ?? DesignDocument::CANVAS_HEIGHT));
        $isCustomCanvas = $canvasW !== DesignDocument::CANVAS_WIDTH || $canvasH !== DesignDocument::CANVAS_HEIGHT;

        if ($isCustomCanvas) {
            $scale = $format === 'png-hd' || $quality === 'high' ? 2 : 1;
            $maxDim = max($canvasW, $canvasH) * $scale;
            if ($maxDim > 4096) {
                $factor = 4096 / max($canvasW, $canvasH);
                $pngW = max(64, (int) round($canvasW * $factor));
                $pngH = max(64, (int) round($canvasH * $factor));
            } else {
                $pngW = $canvasW * $scale;
                $pngH = $canvasH * $scale;
            }
        } else {
            $pngW = $this->widthForQuality($quality);
            $pngH = (int) round($pngW * (DesignDocument::CANVAS_HEIGHT / DesignDocument::CANVAS_WIDTH));
        }

        return match ($format) {
            'svg' => [
                'bytes' => $svg,
                'filename' => "{$slug}-{$variant}.svg",
                'mime' => 'image/svg+xml',
            ],
            'png', 'png-hd' => [
                'bytes' => $this->rasterizer->pngSized($svg, $families, $pngW, $pngH, $variant === 'transparent' ? null : '#FFFFFF'),
                'filename' => "{$slug}-{$variant}-{$pngW}x{$pngH}.png",
                'mime' => 'image/png',
            ],
            'pdf' => [
                'bytes' => $isCustomCanvas
                    ? $this->rasterizer->pdfSized($svg, $families, $canvasW, $canvasH, $variant === 'transparent' ? null : '#FFFFFF')
                    : $this->rasterizer->pdf($svg, $families, $variant === 'transparent' ? null : '#FFFFFF'),
                'filename' => "{$slug}-{$variant}.pdf",
                'mime' => 'application/pdf',
            ],
            'zip' => $this->brandKit($design, $slug),
            default => throw new RuntimeException("Unsupported export format [{$format}]."),
        };
    }

    /**
     * @return array{bytes: string, filename: string, mime: string}
     */
    private function brandKit(UserDesign $design, string $slug): array
    {
        $path = tempnam(sys_get_temp_dir(), 'logokit');
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create the brand kit archive.');
        }

        $variants = ['full', 'transparent', 'black', 'white', 'icon', 'horizontal', 'vertical'];

        foreach ($variants as $variant) {
            $document = $this->variantDesign($design, $variant);
            $svg = $this->renderer->sanitize($this->renderer->render(
                $document,
                ['transparent' => in_array($variant, ['transparent', 'icon'], true)]
            ));

            $zip->addFromString("{$slug}/{$slug}-{$variant}.svg", $svg);

            try {
                $docCanvas = is_array($document['canvas'] ?? null) ? $document['canvas'] : [];
                $cw = max(64, (int) ($docCanvas['width'] ?? DesignDocument::CANVAS_WIDTH));
                $ch = max(64, (int) ($docCanvas['height'] ?? DesignDocument::CANVAS_HEIGHT));
                $scale = 2000 / max($cw, $ch);
                $kitW = max(64, (int) round($cw * $scale));
                $kitH = max(64, (int) round($ch * $scale));
                $png = $this->rasterizer->pngSized(
                    $svg,
                    $this->fontFamilies($document),
                    $kitW,
                    $kitH,
                    $variant === 'transparent' ? null : '#FFFFFF'
                );
                $zip->addFromString("{$slug}/{$slug}-{$variant}.png", $png);
            } catch (Throwable) {
                // Vector output is still delivered when no rasteriser is present.
            }
        }

        $zip->close();
        $bytes = (string) file_get_contents($path);
        @unlink($path);

        return [
            'bytes' => $bytes,
            'filename' => "{$slug}-brand-kit.zip",
            'mime' => 'application/zip',
        ];
    }

    public function storeDownload(UserDesign $design, array $file, string $format, string $variant): Download
    {
        $path = 'exports/'.Str::uuid().'/'.$file['filename'];
        Storage::disk('public')->put($path, $file['bytes']);

        return Download::create([
            'user_id' => $design->user_id,
            'user_design_id' => $design->id,
            'format' => $format,
            'variant' => $variant,
            'path' => $path,
            'bytes' => strlen($file['bytes']),
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    public function store(UserDesign $design, array $file, string $format, string $variant): string
    {
        return (string) $this->storeDownload($design, $file, $format, $variant)->path;
    }

    /**
     * Apply brand-kit transformations to a saved design.
     */
    public function variantDesign(UserDesign $design, string $variant): array
    {
        $document = $design->design_json;

        if (! is_array($document) || ! isset($document['objects'])) {
            throw new RuntimeException('This design has no renderable document.');
        }

        return match ($variant) {
            'black' => $this->renderer->monochrome($document, '#000000'),
            'white' => $this->renderer->monochrome($document, '#FFFFFF'),
            'icon' => $this->iconOnly($document),
            'horizontal' => $this->recompose($design, 'icon-left', $document),
            'vertical' => $this->recompose($design, 'stacked', $document),
            default => $document,
        };
    }

    private function iconOnly(array $document): array
    {
        $objects = [];

        foreach ($document['objects'] as $object) {
            if (in_array($object['role'] ?? null, ['background', 'logo'], true)) {
                if (($object['role'] ?? null) === 'logo') {
                    $object['left'] = DesignDocument::CANVAS_WIDTH / 2;
                    $object['top'] = DesignDocument::CANVAS_HEIGHT / 2;
                    $object['scaleX'] = 300 / (float) ($object['pathBox'] ?? 64);
                    $object['scaleY'] = $object['scaleX'];
                }

                $objects[] = $object;
            }
        }

        $document['objects'] = $objects;

        return $document;
    }

    private function recompose(UserDesign $design, string $layout, array $fallback): array
    {
        $meta = $design->design_json['meta'] ?? [];
        $font = Font::query()->where('slug', $meta['font'] ?? '')->first();
        $symbol = LogoAsset::query()->where('slug', $meta['symbol'] ?? '')->first();
        $palette = $meta['palette'] ?? null;

        if ($font === null) {
            return $fallback;
        }

        $paletteModel = new \App\Models\ColorPalette([
            'name' => 'Design palette',
            'slug' => 'design-palette',
            'colors' => is_array($palette) && $palette !== [] ? $palette : ['#111827', '#2563EB', '#06B6D4'],
            'is_dark' => false,
        ]);

        return $this->composer->compose([
            'brand_name' => $design->business_name,
            'tagline' => $design->tagline,
            'symbol' => $symbol,
            'font' => $font,
            'palette' => $paletteModel,
            'layout' => $layout,
            'style' => $meta['style'] ?? 'modern',
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function fontFamilies(array $document): array
    {
        $families = [];

        $walk = function (array $objects) use (&$walk, &$families): void {
            foreach ($objects as $object) {
                if (($object['type'] ?? null) === 'i-text' && isset($object['fontFamily'])) {
                    $families[] = (string) $object['fontFamily'];
                }

                if (! empty($object['objects'])) {
                    $walk($object['objects']);
                }
            }
        };

        $walk($document['objects'] ?? []);

        return array_values(array_unique($families));
    }

    private function widthForQuality(string $quality): int
    {
        return match ($quality) {
            'standard' => 1200,
            'print' => 4000,
            default => 2400,
        };
    }
}
