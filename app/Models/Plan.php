<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_cents',
        'currency',
        'interval',
        'features',
        'limits',
        'is_popular',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'limits' => 'array',
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function limit(string $key, int $default = 0): int
    {
        return (int) ($this->limits[$key] ?? $default);
    }

    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'price_cents' => $this->price_cents,
            'price' => number_format($this->price_cents / 100, 2, '.', ''),
            'currency' => $this->currency,
            'interval' => $this->interval,
            'features' => $this->features ?? [],
            'is_popular' => $this->is_popular,
        ];
    }
}
