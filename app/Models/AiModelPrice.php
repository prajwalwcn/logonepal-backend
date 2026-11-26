<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiModelPrice extends Model
{
    protected $fillable = [
        'provider',
        'model',
        'input_per_million',
        'output_per_million',
        'currency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'input_per_million' => 'decimal:6',
            'output_per_million' => 'decimal:6',
            'is_active' => 'boolean',
        ];
    }
}
