<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SocialPlatform */
class SocialPlatformResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'accent_color' => $this->accent_color,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'templates_count' => $this->whenCounted('templates'),
            'design_types' => SocialDesignTypeResource::collection($this->whenLoaded('designTypes')),
        ];
    }
}
