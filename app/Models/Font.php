<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Font extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'family',
        'google_family',
        'category',
        'weights',
        'has_italic',
        'preview_text',
        'letter_spacing_by_weight',
        'is_premium',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'weights' => 'array',
            'has_italic' => 'boolean',
            'letter_spacing_by_weight' => 'array',
            'is_premium' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function supportsWeight(int $weight): bool
    {
        $weights = $this->weights ?? [400];

        return $weight >= min($weights) && $weight <= max($weights);
    }
}
