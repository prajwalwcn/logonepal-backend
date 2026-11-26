<?php

namespace App\Services\Logo;

use App\Models\LogoAsset;
use DOMDocument;
use DOMElement;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Imports arbitrary CC0 / custom SVG vector illustrations into `logo_assets`,
 * preserving multi-colour layers, opacity hierarchy, and linear gradients.
 */
class SvgAssetImporter
{
    public function __construct(private readonly SvgSanitizer $sanitizer)
    {
    }

    /**
     * Import a single raw SVG document string into `logo_assets`.
     *
     * @param  array{
     *     name: string,
     *     slug?: string,
     *     category?: string,
     *     keywords?: array<int, string>,
     *     styles?: array<int, string>,
     *     industries?: array<int, string>,
     *     preserve_colors?: bool,
     *     is_premium?: bool,
     *     sort_order?: int
     * }  $meta
     */
    public function importSvg(string $svgContent, array $meta): ?LogoAsset
    {
        $cleanSvg = $this->sanitizer->sanitize($svgContent);
        $dom = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($cleanSvg, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if (! $loaded || $dom->documentElement === null) {
            return null;
        }

        $root = $dom->documentElement;
        [$viewBox, $vbWidth, $vbHeight] = $this->resolveViewBox($root);
        $gradients = $this->extractLinearGradients($dom);

        $paths = [];
        $colorToTone = [];
        $nextTone = 1;
        $preserveColors = (bool) ($meta['preserve_colors'] ?? false);

        foreach ($dom->getElementsByTagName('*') as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($node->nodeName);
            if (! in_array($tag, ['path', 'polygon', 'polyline', 'circle', 'ellipse', 'rect'], true)) {
                continue;
            }

            // Skip elements inside <defs> or <clipPath>.
            if ($this->insideDefs($node)) {
                continue;
            }

            $fillAttr = trim($node->getAttribute('fill'));
            if (strtolower($fillAttr) === 'none') {
                continue;
            }

            $d = $this->elementToPathD($node, $tag);
            if ($d === '' || $this->isFullCanvasRect($d, $vbWidth, $vbHeight, $tag, $node)) {
                continue;
            }

            $entry = ['d' => $d];

            if (preg_match('/url\(#([^)]+)\)/i', $fillAttr, $gradMatch) && isset($gradients[$gradMatch[1]])) {
                $entry['gradient'] = $gradients[$gradMatch[1]];
                $entry['tone'] = 1;
            } elseif (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $fillAttr)) {
                $hex = $this->normalizeHex($fillAttr);
                if (! isset($colorToTone[$hex])) {
                    $colorToTone[$hex] = min(6, $nextTone++);
                }
                $entry['tone'] = $colorToTone[$hex];
                if ($preserveColors) {
                    $entry['color'] = $hex;
                    $entry['preserve_color'] = true;
                }
            } else {
                $entry['tone'] = 1;
            }

            $opacityStr = trim($node->getAttribute('fill-opacity') ?: $node->getAttribute('opacity'));
            if ($opacityStr !== '' && is_numeric($opacityStr)) {
                $op = (float) $opacityStr;
                if ($op > 0 && $op < 0.99) {
                    $entry['fill_opacity'] = round($op, 2);
                }
            }

            $rule = strtolower(trim($node->getAttribute('fill-rule')));
            if ($rule === 'evenodd') {
                $entry['fill_rule'] = 'evenodd';
            }

            $paths[] = $entry;
        }

        if ($paths === []) {
            return null;
        }

        $name = trim($meta['name']);
        $slug = $meta['slug'] ?? Str::slug($name);
        $category = mb_strtolower(trim((string) ($meta['category'] ?? 'abstract')));
        $keywords = array_values(array_unique(array_filter(array_merge(
            [$category],
            array_map('mb_strtolower', (array) ($meta['keywords'] ?? [])),
            preg_split('/[^a-z0-9]+/i', mb_strtolower($name)) ?: []
        ), fn ($w) => is_string($w) && mb_strlen($w) >= 2)));

        $asset = LogoAsset::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'view_box' => $viewBox,
                'paths' => $paths,
                'keywords' => $keywords,
                'style_slugs' => $meta['styles'] ?? ['modern', 'minimal'],
                'industry_slugs' => $meta['industries'] ?? ['general'],
                'is_premium' => (bool) ($meta['is_premium'] ?? false),
                'is_active' => true,
                'sort_order' => (int) ($meta['sort_order'] ?? 0),
            ]
        );

        Cache::forget('logoforge:symbol-vocab:v2');

        return $asset;
    }

    /**
     * Import a transparent PNG/WebP raster emblem into `logo_assets` and copy it into public storage.
     *
     * @param  array{
     *     name: string,
     *     slug?: string,
     *     category?: string,
     *     keywords?: array<int, string>,
     *     styles?: array<int, string>,
     *     industries?: array<int, string>,
     *     is_premium?: bool,
     *     sort_order?: int
     * }  $meta
     */
    public function importImage(string $filePath, array $meta): ?LogoAsset
    {
        if (! is_file($filePath)) {
            return null;
        }

        $info = @getimagesize($filePath);
        if ($info === false || ! in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_JPEG], true)) {
            return null;
        }

        $width = max(1, (int) $info[0]);
        $height = max(1, (int) $info[1]);
        $ext = match ($info[2]) {
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_JPEG => 'jpg',
            default => 'png',
        };

        $name = trim($meta['name']);
        $slug = $meta['slug'] ?? Str::slug($name);
        $targetDir = storage_path('app/public/symbols');
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $filename = $slug.'.'.$ext;
        copy($filePath, $targetDir.DIRECTORY_SEPARATOR.$filename);

        $category = mb_strtolower(trim((string) ($meta['category'] ?? 'abstract')));
        $keywords = array_values(array_unique(array_filter(array_merge(
            [$category],
            array_map('mb_strtolower', (array) ($meta['keywords'] ?? [])),
            preg_split('/[^a-z0-9]+/i', mb_strtolower($name)) ?: []
        ), fn ($w) => is_string($w) && mb_strlen($w) >= 2)));

        $asset = LogoAsset::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'view_box' => "0 0 {$width} {$height}",
                'asset_type' => 'png',
                'image_url' => '/storage/symbols/'.$filename,
                'paths' => [],
                'keywords' => $keywords,
                'style_slugs' => $meta['styles'] ?? ['modern', 'bold'],
                'industry_slugs' => $meta['industries'] ?? ['general'],
                'is_premium' => (bool) ($meta['is_premium'] ?? false),
                'is_active' => true,
                'sort_order' => (int) ($meta['sort_order'] ?? 0),
            ]
        );

        Cache::forget('logoforge:symbol-vocab:v2');

        return $asset;
    }

    /**
     * Recursively import every .svg, .png, and .webp file inside a folder into `logo_assets`.
     *
     * @param  array{category?: string, styles?: array<int, string>, industries?: array<int, string>, preserve_colors?: bool, is_premium?: bool}  $defaults
     * @return array{imported: int, skipped: int, slugs: array<int, string>}
     */
    public function importDirectory(string $directory, array $defaults = []): array
    {
        if (! is_dir($directory)) {
            return ['imported' => 0, 'skipped' => 0, 'slugs' => []];
        }

        $imported = 0;
        $skipped = 0;
        $slugs = [];

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if (! $file instanceof \SplFileInfo || ! $file->isFile()) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            if (! in_array($ext, ['svg', 'png', 'webp'], true)) {
                continue;
            }

            $rawName = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $parentDir = basename($file->getPath());
            $inferredCategory = ($parentDir !== '' && $parentDir !== basename($directory))
                ? Str::slug($parentDir)
                : ($defaults['category'] ?? 'abstract');

            $displayName = Str::title(str_replace(['-', '_'], ' ', $rawName));
            $keywords = array_filter(explode('-', Str::slug($rawName)));
            $meta = [
                'name' => $displayName,
                'slug' => 'custom-'.Str::slug($inferredCategory.'-'.$rawName),
                'category' => $inferredCategory,
                'keywords' => $keywords,
                'styles' => $defaults['styles'] ?? ['modern', 'bold'],
                'industries' => array_values(array_unique(array_merge(
                    [$inferredCategory],
                    $defaults['industries'] ?? ['general']
                ))),
                'preserve_colors' => (bool) ($defaults['preserve_colors'] ?? true),
                'is_premium' => (bool) ($defaults['is_premium'] ?? false),
                'sort_order' => $imported + 50,
            ];

            $asset = $ext === 'svg'
                ? $this->importSvg((string) file_get_contents($file->getPathname()), $meta)
                : $this->importImage($file->getPathname(), $meta);

            if ($asset !== null) {
                $imported++;
                $slugs[] = $asset->slug;
            } else {
                $skipped++;
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'slugs' => $slugs];
    }

    /**
     * @return array{0: string, 1: float, 2: float}
     */
    private function resolveViewBox(DOMElement $root): array
    {
        $vb = trim($root->getAttribute('viewBox'));
        if ($vb !== '') {
            $parts = preg_split('/[\s,]+/', $vb) ?: [];
            if (count($parts) === 4 && (float) $parts[2] > 0 && (float) $parts[3] > 0) {
                return [implode(' ', $parts), (float) $parts[2], (float) $parts[3]];
            }
        }

        $w = (float) preg_replace('/[^0-9.]/', '', $root->getAttribute('width') ?: '512');
        $h = (float) preg_replace('/[^0-9.]/', '', $root->getAttribute('height') ?: (string) $w);
        $w = $w > 0 ? $w : 512.0;
        $h = $h > 0 ? $h : $w;

        return ["0 0 {$w} {$h}", $w, $h];
    }

    /**
     * @return array<string, array{type: 'linear', angle: float, stops: array<int, array{offset: float, color: string}>}>
     */
    private function extractLinearGradients(DOMDocument $dom): array
    {
        $map = [];

        foreach ($dom->getElementsByTagName('linearGradient') as $grad) {
            if (! $grad instanceof DOMElement) {
                continue;
            }

            $id = trim($grad->getAttribute('id'));
            if ($id === '') {
                continue;
            }

            $stops = [];
            foreach ($grad->getElementsByTagName('stop') as $stop) {
                $offsetRaw = trim($stop->getAttribute('offset'));
                $offset = str_ends_with($offsetRaw, '%')
                    ? ((float) rtrim($offsetRaw, '%')) / 100.0
                    : (float) ($offsetRaw === '' ? (count($stops) === 0 ? 0 : 1) : $offsetRaw);
                $color = trim($stop->getAttribute('stop-color') ?: '#111827');

                if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $color)) {
                    $stops[] = ['offset' => round($offset, 3), 'color' => $this->normalizeHex($color)];
                }
            }

            if (count($stops) < 2) {
                continue;
            }

            $x1 = (float) rtrim($grad->getAttribute('x1') ?: '0', '%');
            $y1 = (float) rtrim($grad->getAttribute('y1') ?: '0', '%');
            $x2 = (float) rtrim($grad->getAttribute('x2') ?: '100', '%');
            $y2 = (float) rtrim($grad->getAttribute('y2') ?: '100', '%');
            $angle = round(rad2deg(atan2($y2 - $y1, $x2 - $x1)));

            $map[$id] = [
                'type' => 'linear',
                'angle' => $angle,
                'stops' => $stops,
            ];
        }

        return $map;
    }

    private function elementToPathD(DOMElement $node, string $tag): string
    {
        if ($tag === 'path') {
            return trim($node->getAttribute('d'));
        }

        if ($tag === 'rect') {
            $x = (float) ($node->getAttribute('x') ?: 0);
            $y = (float) ($node->getAttribute('y') ?: 0);
            $w = (float) ($node->getAttribute('width') ?: 0);
            $h = (float) ($node->getAttribute('height') ?: 0);

            return ($w > 0 && $h > 0) ? "M {$x} {$y} H ".($x + $w).' V '.($y + $h)." H {$x} Z" : '';
        }

        if ($tag === 'circle') {
            $cx = (float) ($node->getAttribute('cx') ?: 0);
            $cy = (float) ($node->getAttribute('cy') ?: 0);
            $r = (float) ($node->getAttribute('r') ?: 0);

            return $r > 0 ? 'M '.($cx - $r)." {$cy} a {$r} {$r} 0 1 0 ".($r * 2)." 0 a {$r} {$r} 0 1 0 -".($r * 2).' 0 Z' : '';
        }

        if ($tag === 'ellipse') {
            $cx = (float) ($node->getAttribute('cx') ?: 0);
            $cy = (float) ($node->getAttribute('cy') ?: 0);
            $rx = (float) ($node->getAttribute('rx') ?: 0);
            $ry = (float) ($node->getAttribute('ry') ?: 0);

            return ($rx > 0 && $ry > 0) ? 'M '.($cx - $rx)." {$cy} a {$rx} {$ry} 0 1 0 ".($rx * 2)." 0 a {$rx} {$ry} 0 1 0 -".($rx * 2).' 0 Z' : '';
        }

        if ($tag === 'polygon' || $tag === 'polyline') {
            $pts = array_values(array_filter(preg_split('/[\s,]+/', trim($node->getAttribute('points'))) ?: []));
            if (count($pts) < 6) {
                return '';
            }
            $cmds = [];
            for ($i = 0; $i + 1 < count($pts); $i += 2) {
                $cmds[] = ($i === 0 ? 'M ' : 'L ').$pts[$i].' '.$pts[$i + 1];
            }

            return implode(' ', $cmds).($tag === 'polygon' ? ' Z' : '');
        }

        return '';
    }

    private function insideDefs(DOMElement $node): bool
    {
        $cur = $node->parentNode;
        while ($cur instanceof DOMElement) {
            if (in_array(strtolower($cur->nodeName), ['defs', 'clippath'], true)) {
                return true;
            }
            $cur = $cur->parentNode;
        }

        return false;
    }

    private function isFullCanvasRect(string $d, float $vw, float $vh, string $tag, DOMElement $node): bool
    {
        if ($tag === 'rect') {
            $x = (float) ($node->getAttribute('x') ?: 0);
            $y = (float) ($node->getAttribute('y') ?: 0);
            $w = (float) ($node->getAttribute('width') ?: 0);
            $h = (float) ($node->getAttribute('height') ?: 0);

            return $x <= 1 && $y <= 1 && $w >= $vw * 0.96 && $h >= $vh * 0.96;
        }

        $compact = strtolower(preg_replace('/\s+/', '', $d) ?? '');
        $w = (string) (int) round($vw);
        $h = (string) (int) round($vh);

        return in_array($compact, [
            "m0,0h{$w}v{$h}h0z",
            "m00h{$w}v{$h}h0z",
            "m0,0h{$w}v{$h}h-{$w}z",
            "m00h{$w}v{$h}h-{$w}z",
        ], true);
    }

    private function normalizeHex(string $hex): string
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return '#'.strtoupper($hex);
    }
}
