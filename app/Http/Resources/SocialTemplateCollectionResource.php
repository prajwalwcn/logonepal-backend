<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SocialTemplateCollection */
class SocialTemplateCollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'cover_path' => $this->cover_path,
            'is_active' => (bool) $this->is_active,
            'templates_count' => $this->whenCounted('templates'),
            'templates' => SocialTemplateSummaryResource::collection($this->whenLoaded('templates')),
        ];
    }
}
