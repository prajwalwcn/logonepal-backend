<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AiPrompt extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'category',
        'workflow',
        'content',
        'variables',
        'version',
        'status',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
            'version' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $prompt) {
            $prompt->slug = $prompt->slug ?: Str::slug($prompt->name).'-'.Str::lower(Str::random(5));
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('status', 'published');
    }
}
