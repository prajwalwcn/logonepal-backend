<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SocialTemplateVariation */
class SocialTemplateVariationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'status' => $this->status,
            'fingerprint' => $this->fingerprint,
            'quality_score' => $this->quality_score,
            'svg' => $this->svg,
            'design' => $this->design_json,
            'meta' => $this->meta,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
