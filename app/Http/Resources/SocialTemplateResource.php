<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SocialTemplate */
class SocialTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return array_merge(
            (new SocialTemplateSummaryResource($this->resource))->resolve($request),
            [
                'design' => $this->design_json,
                'svg' => $this->svg,
                'layer_config' => $this->layer_config,
                'asset_refs' => $this->asset_refs,
                'license_type' => $this->license_type,
                'version' => $this->version,
                'source' => $this->source,
                'ai_meta' => $this->ai_meta,
                'safe_area' => $this->safeArea(),
                'quality' => $this->quality_report,
                'versions_count' => $this->whenCounted('versions'),
                'variations_count' => $this->whenCounted('variations'),
                'favorites_count' => $this->whenCounted('favorites'),
            ]
        );
    }
}
