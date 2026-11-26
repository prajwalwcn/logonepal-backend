<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\GeneratedLogo */
class GeneratedLogoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'layout' => $this->layout,
            'orientation' => $this->orientation,
            'style' => $this->style_slug,
            'score' => (float) $this->score,
            'is_premium' => (bool) $this->is_premium,
            'palette' => $this->palette ?? [],
            'font' => $this->whenLoaded('font', fn () => [
                'slug' => $this->font?->slug,
                'name' => $this->font?->name,
                'family' => $this->font?->family,
                'category' => $this->font?->category,
            ]),
            'symbol' => $this->whenLoaded('symbolAsset', fn () => $this->symbolAsset?->slug),
            'svg' => $this->svg,
            'preview_url' => route('api.logos.preview', ['logo' => $this->id]),
            'design' => $this->design_json,
        ];
    }
}
