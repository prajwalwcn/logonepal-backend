<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiProvider extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'driver',
        'model',
        'image_model',
        'base_url',
        'api_key',
        'is_active',
        'priority',
        'meta',
    ];

    protected $hidden = ['api_key'];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }
}
