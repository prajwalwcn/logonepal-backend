<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TypographyPreset extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'category',
        'description',
        'heading_font',
        'body_font',
        'accent_font',
        'heading_weight',
        'body_weight',
        'scale_ratio',
        'line_height_heading',
        'line_height_body',
        'letter_spacing_heading',
        'letter_spacing_body',
        'text_alignment',
        'rules',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'rules' => 'array',
            'scale_ratio' => 'float',
            'line_height_heading' => 'float',
            'line_height_body' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
