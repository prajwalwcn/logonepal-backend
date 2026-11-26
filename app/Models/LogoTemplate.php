<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LogoTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_token',
        'name',
        'slug',
        'description',
        'industry_id',
        'category_id',
        'style_id',
        'default_font_id',
        'symbol_asset_id',
        'layout',
        'orientation',
        'keywords',
        'design_json',
        'svg',
        'preview_path',
        'is_premium',
        'is_active',
        'status',
        'version',
        'popularity',
        'downloads_count',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'design_json' => 'array',
            'is_premium' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $template) {
            $template->template_token ??= (string) Str::uuid();
        });

        static::saving(function (self $template) {
            $template->slug = $template->slug ?: Str::slug($template->name).'-'.Str::lower(Str::random(5));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function style(): BelongsTo
    {
        return $this->belongsTo(LogoStyle::class, 'style_id');
    }

    public function font(): BelongsTo
    {
        return $this->belongsTo(Font::class, 'default_font_id');
    }

    public function symbol(): BelongsTo
    {
        return $this->belongsTo(LogoAsset::class, 'symbol_asset_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
