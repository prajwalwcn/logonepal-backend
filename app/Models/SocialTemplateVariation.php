<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SocialTemplateVariation extends Model
{
    protected $fillable = [
        'uuid',
        'template_id',
        'name',
        'design_json',
        'svg',
        'preview_path',
        'fingerprint',
        'status',
        'meta',
        'quality_score',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'design_json' => 'array',
            'meta' => 'array',
            'quality_score' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $variation) => $variation->uuid ??= (string) Str::uuid());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SocialTemplate::class, 'template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
