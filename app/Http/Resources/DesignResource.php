<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\UserDesign */
class DesignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'business_name' => $this->business_name,
            'tagline' => $this->tagline,
            'format' => $this->format,
            'design' => $this->design_json,
            'svg' => $this->svg,
            'is_draft' => (bool) $this->is_draft,
            'can_edit' => $request->user()?->id === $this->user_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'last_edited_at' => $this->last_edited_at?->toIso8601String(),
        ];
    }
}
