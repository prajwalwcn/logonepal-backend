<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LayoutPreset extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'layout_type',
        'description',
        'compatible_formats',
        'proportions',
        'alignment',
        'safe_area',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'compatible_formats' => 'array',
            'proportions' => 'array',
            'safe_area' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function supportsFormat(string $formatCategory): bool
    {
        $formats = $this->compatible_formats ?? [];
        if ($formats === []) {
            return true;
        }

        return in_array($formatCategory, $formats, true);
    }
}
