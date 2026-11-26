<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ColorPalette extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'colors',
        'mood',
        'category',
        'industry_slug',
        'background_color',
        'heading_color',
        'body_color',
        'accent_color',
        'contrast_ratio',
        'is_dark',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'colors' => 'array',
            'is_dark' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function primary(): string
    {
        return $this->colors[0] ?? '#111827';
    }

    public function secondary(): string
    {
        return $this->colors[1] ?? $this->primary();
    }
}
