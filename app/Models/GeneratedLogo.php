<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedLogo extends Model
{
    protected $fillable = [
        'generation_id',
        'template_id',
        'symbol_asset_id',
        'font_id',
        'style_slug',
        'layout',
        'orientation',
        'palette',
        'design_json',
        'svg',
        'score',
        'position',
        'is_premium',
        'is_ai_symbol',
    ];

    protected function casts(): array
    {
        return [
            'palette' => 'array',
            'design_json' => 'array',
            'score' => 'float',
            'is_premium' => 'boolean',
            'is_ai_symbol' => 'boolean',
        ];
    }

    public function generation(): BelongsTo
    {
        return $this->belongsTo(LogoGeneration::class, 'generation_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(LogoTemplate::class, 'template_id');
    }

    public function font(): BelongsTo
    {
        return $this->belongsTo(Font::class);
    }

    public function symbolAsset(): BelongsTo
    {
        return $this->belongsTo(LogoAsset::class, 'symbol_asset_id');
    }
}
