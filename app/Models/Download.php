<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Download extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_design_id',
        'format',
        'variant',
        'path',
        'width',
        'height',
        'bytes',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(UserDesign::class, 'user_design_id');
    }
}
