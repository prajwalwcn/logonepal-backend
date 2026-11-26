<?php

namespace App\Models;

use App\Support\Color;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogoAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'view_box',
        'asset_type',
        'category',
        'image_url',
        'paths',
        'keywords',
        'style_slugs',
        'industry_slugs',
        'is_premium',
        'is_active',
        'license',
        'attribution',
        'commercial_use',
        'source',
        'mime_type',
        'format',
        'file_size',
        'width',
        'height',
        'is_featured',
        'is_archived',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'paths' => 'array',
            'keywords' => 'array',
            'style_slugs' => 'array',
            'industry_slugs' => 'array',
            'is_premium' => 'boolean',
            'is_active' => 'boolean',
            'commercial_use' => 'boolean',
            'is_featured' => 'boolean',
            'is_archived' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Resolve a local /storage/ PNG or WebP asset into a self-contained data URI
     * so standalone SVG previews and exports never break offline.
     */
    public function resolvedImageDataUri(): ?string
    {
        $url = $this->image_url;
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        if (str_starts_with($url, 'data:image/')) {
            return $url;
        }

        if (str_starts_with($url, '/storage/')) {
            $relative = ltrim(substr($url, strlen('/storage/')), '/\\');
            $diskPath = storage_path('app/public/'.$relative);

            if (is_file($diskPath)) {
                $ext = strtolower(pathinfo($diskPath, PATHINFO_EXTENSION));
                $mime = match ($ext) {
                    'webp' => 'image/webp',
                    'jpg', 'jpeg' => 'image/jpeg',
                    default => 'image/png',
                };
                $bytes = file_get_contents($diskPath);
                if ($bytes !== false) {
                    return 'data:'.$mime.';base64,'.base64_encode($bytes);
                }
            }
        }

        return $url;
    }

    /**
     * Render the symbol as a standalone SVG string supporting multi-tone paths,
     * preserved native multi-colour illustrations, opacity layers, gradients,
     * and transparent raster PNG/WebP symbols.
     */
    public function toSvg(string $primary, ?string $secondary = null, ?string $tertiary = null): string
    {
        if (($this->asset_type ?? 'svg') === 'png' && $this->image_url) {
            $href = e($this->image_url);

            return sprintf(
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="64" height="64"><image href="%s" x="0" y="0" width="512" height="512" preserveAspectRatio="xMidYMid meet"/></svg>',
                $href
            );
        }

        $secondary ??= $primary;
        $tertiary ??= Color::shade($secondary, 0.25);
        $quaternary = Color::shade($primary, -0.2);

        $defs = [];
        $body = '';
        $slugPrefix = substr(md5((string) $this->slug), 0, 8);

        foreach ($this->paths as $index => $path) {
            $tone = (int) ($path['tone'] ?? 1);
            $d = e((string) ($path['d'] ?? ''));

            if ($d === '') {
                continue;
            }

            if (isset($path['gradient']) && is_array($path['gradient']) && ! empty($path['gradient']['stops'])) {
                $gradId = sprintf('la-g-%s-%d', $slugPrefix, $index + 1);
                $angle = (float) ($path['gradient']['angle'] ?? 135);
                $rad = deg2rad($angle);
                $x1 = round(50 - cos($rad) * 50, 1);
                $y1 = round(50 - sin($rad) * 50, 1);
                $x2 = round(50 + cos($rad) * 50, 1);
                $y2 = round(50 + sin($rad) * 50, 1);
                $stops = '';

                foreach ($path['gradient']['stops'] as $stop) {
                    $stops .= sprintf(
                        '<stop offset="%s" stop-color="%s"/>',
                        (string) ($stop['offset'] ?? 0),
                        e((string) ($stop['color'] ?? $primary))
                    );
                }

                $defs[] = sprintf(
                    '<linearGradient id="%s" x1="%s%%" y1="%s%%" x2="%s%%" y2="%s%%">%s</linearGradient>',
                    $gradId,
                    $x1,
                    $y1,
                    $x2,
                    $y2,
                    $stops
                );
                $fill = 'url(#'.$gradId.')';
            } elseif (! empty($path['preserve_color']) && ! empty($path['color'])) {
                $fill = e((string) $path['color']);
            } else {
                $fill = match ($tone) {
                    1 => $primary,
                    2 => $secondary,
                    3 => $tertiary,
                    default => $quaternary,
                };
            }

            $rule = isset($path['fill_rule']) ? ' fill-rule="'.e((string) $path['fill_rule']).'" clip-rule="'.e((string) $path['fill_rule']).'"' : '';
            $opacity = isset($path['fill_opacity']) && (float) $path['fill_opacity'] < 0.99
                ? ' fill-opacity="'.round((float) $path['fill_opacity'], 2).'"'
                : '';

            $body .= sprintf(
                '<path id="symbol-path-%d" data-tone="%d" d="%s" fill="%s"%s%s/>',
                $index + 1,
                $tone,
                $d,
                $fill,
                $opacity,
                $rule
            );
        }

        $defsMarkup = $defs === [] ? '' : '<defs>'.implode('', $defs).'</defs>';

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="%s" width="64" height="64">%s%s</svg>',
            e($this->view_box),
            $defsMarkup,
            $body
        );
    }
}
