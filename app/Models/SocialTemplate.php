<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SocialTemplate extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'platform_id',
        'design_type_id',
        'industry_id',
        'category_id',
        'width',
        'height',
        'aspect_ratio',
        'styles',
        'keywords',
        'tags',
        'palette',
        'fonts',
        'preview_path',
        'thumbnail_path',
        'design_json',
        'svg',
        'layer_config',
        'asset_refs',
        'license_type',
        'is_premium',
        'is_featured',
        'is_trending',
        'status',
        'version',
        'source',
        'fingerprint',
        'ai_meta',
        'quality_score',
        'quality_report',
        'created_by',
        'published_at',
        'usage_count',
        'view_count',
        'favorite_count',
    ];

    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
            'styles' => 'array',
            'keywords' => 'array',
            'tags' => 'array',
            'palette' => 'array',
            'fonts' => 'array',
            'design_json' => 'array',
            'layer_config' => 'array',
            'asset_refs' => 'array',
            'ai_meta' => 'array',
            'quality_report' => 'array',
            'quality_score' => 'integer',
            'is_premium' => 'boolean',
            'is_featured' => 'boolean',
            'is_trending' => 'boolean',
            'version' => 'integer',
            'published_at' => 'datetime',
            'usage_count' => 'integer',
            'view_count' => 'integer',
            'favorite_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $template) {
            $template->uuid ??= (string) Str::uuid();
        });

        static::saving(function (self $template) {
            $template->slug = $template->slug ?: Str::slug($template->name).'-'.Str::lower(Str::random(5));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getIsFreeAttribute(): bool
    {
        return ! $this->is_premium;
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(SocialPlatform::class, 'platform_id');
    }

    public function designType(): BelongsTo
    {
        return $this->belongsTo(SocialDesignType::class, 'design_type_id');
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TemplateCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(SocialTemplateVersion::class, 'template_id')->latest('version');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(SocialTemplateVariation::class, 'template_id')->latest();
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(
            SocialTemplateCollection::class,
            'social_template_collection_items',
            'template_id',
            'collection_id'
        )->withPivot('sort_order');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(SocialTemplateFavorite::class, 'template_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(SocialTemplateEvent::class, 'template_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)->whereNotNull('published_at');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->published();
    }

    public function scopeForPlatform(Builder $query, ?string $platform): Builder
    {
        return $platform ? $query->whereHas('platform', fn (Builder $q) => $q->where('slug', $platform)) : $query;
    }

    public function scopeForDesignType(Builder $query, ?string $designType): Builder
    {
        return $designType ? $query->whereHas('designType', fn (Builder $q) => $q->where('slug', $designType)) : $query;
    }

    public function scopeForIndustry(Builder $query, ?string $industry): Builder
    {
        return $industry ? $query->whereHas('industry', fn (Builder $q) => $q->where('slug', $industry)) : $query;
    }

    public function scopeForCategory(Builder $query, ?string $category): Builder
    {
        return $category ? $query->whereHas('category', fn (Builder $q) => $q->where('slug', $category)) : $query;
    }

    public function scopeForStyle(Builder $query, ?string $style): Builder
    {
        return $style ? $query->whereJsonContains('styles', $style) : $query;
    }

    /**
     * @return array{top: float, right: float, bottom: float, left: float}
     */
    public function safeArea(): array
    {
        if ($this->designType !== null) {
            return $this->designType->safeAreaPixels($this->width, $this->height);
        }

        return [
            'top' => round($this->height * 0.05, 2),
            'right' => round($this->width * 0.05, 2),
            'bottom' => round($this->height * 0.05, 2),
            'left' => round($this->width * 0.05, 2),
        ];
    }
}
