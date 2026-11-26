<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class License extends Model
{
    protected $fillable = [
        'uuid',
        'license_key',
        'user_id',
        'user_design_id',
        'order_id',
        'type',
        'issued_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $license) {
            $license->uuid ??= (string) Str::uuid();
            $license->license_key ??= strtoupper(Str::random(4).'-'.Str::random(4).'-'.Str::random(4).'-'.Str::random(4));
            $license->issued_at ??= now();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(UserDesign::class, 'user_design_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
