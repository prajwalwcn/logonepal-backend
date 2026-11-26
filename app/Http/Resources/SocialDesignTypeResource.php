<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SocialDesignType */
class SocialDesignTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'platform_id' => $this->platform_id,
            'platform' => $this->whenLoaded('platform', fn () => [
                'id' => $this->platform?->id,
                'name' => $this->platform?->name,
                'slug' => $this->platform?->slug,
            ]),
            'name' => $this->name,
            'slug' => $this->slug,
            'width' => $this->width,
            'height' => $this->height,
            'aspect_ratio' => $this->aspect_ratio,
            'safe_area' => $this->safe_area ?? [],
            'export_formats' => $this->export_formats ?? [],
            'layout_family' => $this->layout_family,
            'variant' => $this->variant,
            'notes' => $this->notes,
            'is_active' => (bool) $this->is_active,
            'templates_count' => $this->whenCounted('templates'),
        ];
    }
}
