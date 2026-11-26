<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class UserDesign extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'guest_token',
        'generation_id',
        'generated_logo_id',
        'template_id',
        'format',
        'name',
        'business_name',
        'tagline',
        'design_json',
        'svg',
        'thumbnail_path',
        'is_draft',
        'is_archived',
        'moderation_status',
        'moderated_by',
        'moderated_at',
        'moderation_note',
        'last_edited_at',
    ];

    protected function casts(): array
    {
        return [
            'design_json' => 'array',
            'is_draft' => 'boolean',
            'is_archived' => 'boolean',
            'moderated_at' => 'datetime',
            'last_edited_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $design) {
            $design->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(LogoTemplate::class);
    }

    public function generation(): BelongsTo
    {
        return $this->belongsTo(LogoGeneration::class, 'generation_id');
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(ContentReport::class, 'reportable');
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }
}
