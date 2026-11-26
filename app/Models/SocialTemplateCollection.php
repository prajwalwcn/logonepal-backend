<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class SocialTemplateCollection extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'cover_path',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $collection) => $collection->uuid ??= (string) Str::uuid());
        static::saving(fn (self $collection) => $collection->slug = $collection->slug ?: Str::slug($collection->name));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function templates(): BelongsToMany
    {
        return $this->belongsToMany(
            SocialTemplate::class,
            'social_template_collection_items',
            'collection_id',
            'template_id'
        )->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
