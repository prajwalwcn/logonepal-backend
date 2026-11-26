<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SocialTemplate */
class SocialTemplateSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'platform_id' => $this->platform_id,
            'design_type_id' => $this->design_type_id,
            'industry_id' => $this->industry_id,
            'category_id' => $this->category_id,
            'platform' => $this->whenLoaded('platform', fn () => [
                'id' => $this->platform?->id,
                'name' => $this->platform?->name,
                'slug' => $this->platform?->slug,
                'accent_color' => $this->platform?->accent_color,
            ]),
            'design_type' => $this->whenLoaded('designType', fn () => [
                'id' => $this->designType?->id,
                'name' => $this->designType?->name,
                'slug' => $this->designType?->slug,
                'width' => $this->designType?->width,
                'height' => $this->designType?->height,
                'aspect_ratio' => $this->designType?->aspect_ratio,
            ]),
            'industry' => $this->whenLoaded('industry', fn () => $this->industry?->name),
            'industry_slug' => $this->whenLoaded('industry', fn () => $this->industry?->slug),
            'category' => $this->whenLoaded('category', fn () => $this->category?->name),
            'category_slug' => $this->whenLoaded('category', fn () => $this->category?->slug),
            'width' => $this->width,
            'height' => $this->height,
            'aspect_ratio' => $this->aspect_ratio,
            'styles' => $this->styles ?? [],
            'keywords' => $this->keywords ?? [],
            'tags' => $this->tags ?? [],
            'palette' => $this->palette ?? [],
            'fonts' => $this->fonts ?? [],
            'is_premium' => (bool) $this->is_premium,
            'is_free' => ! $this->is_premium,
            'is_featured' => (bool) $this->is_featured,
            'is_trending' => (bool) $this->is_trending,
            'is_favorited' => (bool) ($this->is_favorited ?? false),
            'status' => $this->status,
            'quality_score' => $this->quality_score,
            'preview_url' => route('api.social-templates.preview', ['template' => $this->slug]),
            'usage_count' => $this->usage_count,
            'view_count' => $this->view_count,
            'favorite_count' => $this->favorite_count,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
