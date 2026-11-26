<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialDesignType extends Model
{
    protected $fillable = [
        'platform_id',
        'name',
        'slug',
        'width',
        'height',
        'aspect_ratio',
        'safe_area',
        'export_formats',
        'layout_family',
        'variant',
        'notes',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
            'safe_area' => 'array',
            'export_formats' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(SocialPlatform::class, 'platform_id');
    }

    public function templates(): HasMany
    {
        return $this->hasMany(SocialTemplate::class, 'design_type_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Safe-area insets in pixels for a concrete canvas.
     *
     * @return array{top: float, right: float, bottom: float, left: float}
     */
    public function safeAreaPixels(?int $width = null, ?int $height = null): array
    {
        $width ??= $this->width;
        $height ??= $this->height;
        $area = is_array($this->safe_area) ? $this->safe_area : [];

        return [
            'top' => round(max(0, min(0.45, (float) ($area['top'] ?? 0.05))) * $height, 2),
            'right' => round(max(0, min(0.45, (float) ($area['right'] ?? 0.05))) * $width, 2),
            'bottom' => round(max(0, min(0.45, (float) ($area['bottom'] ?? 0.05))) * $height, 2),
            'left' => round(max(0, min(0.45, (float) ($area['left'] ?? 0.05))) * $width, 2),
        ];
    }
}
