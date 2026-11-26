<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentPage extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'type',
        'excerpt',
        'body',
        'hero_image',
        'sections',
        'seo_title',
        'seo_description',
        'og_image',
        'status',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'sections' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}
