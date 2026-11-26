<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGenerationLog extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'purpose',
        'model',
        'prompt_hash',
        'status',
        'job_id',
        'tokens_in',
        'tokens_out',
        'latency_ms',
        'estimated_cost',
        'currency',
        'review_status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'error',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'estimated_cost' => 'decimal:6',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
