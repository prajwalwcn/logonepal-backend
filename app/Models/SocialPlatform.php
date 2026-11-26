<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialPlatform extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'accent_color',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function designTypes(): HasMany
    {
        return $this->hasMany(SocialDesignType::class, 'platform_id')->orderBy('sort_order');
    }

    public function templates(): HasMany
    {
        return $this->hasMany(SocialTemplate::class, 'platform_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
