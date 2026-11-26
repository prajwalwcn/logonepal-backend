<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignPreset extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'style_key',
        'description',
        'color_palette_id',
        'typography_preset_id',
        'layout_preset_id',
        'heading_font',
        'body_font',
        'font_hierarchy',
        'spacing_rules',
        'layout_type',
        'image_placement',
        'background_style',
        'shape_style',
        'decoration_rules',
        'border_shadow_settings',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'font_hierarchy' => 'array',
            'spacing_rules' => 'array',
            'decoration_rules' => 'array',
            'border_shadow_settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function colorPalette(): BelongsTo
    {
        return $this->belongsTo(ColorPalette::class);
    }

    public function typographyPreset(): BelongsTo
    {
        return $this->belongsTo(TypographyPreset::class);
    }

    public function layoutPreset(): BelongsTo
    {
        return $this->belongsTo(LayoutPreset::class);
    }
}
