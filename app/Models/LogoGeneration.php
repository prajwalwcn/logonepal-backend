<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LogoGeneration extends Model
{
    protected $fillable = [
        'uuid',
        'business_name',
        'normalized_name',
        'engine',
        'status',
        'intent',
        'filters',
        'total_count',
        'user_id',
        'guest_token',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'intent' => 'array',
            'filters' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $generation) {
            $generation->uuid ??= (string) Str::uuid();
            $generation->normalized_name ??= Str::lower(trim($generation->business_name));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function logos(): HasMany
    {
        return $this->hasMany(GeneratedLogo::class, 'generation_id')->orderBy('position');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
