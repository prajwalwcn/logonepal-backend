<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSocialDesignTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $designType = $this->route('designType');
        $platform = $this->route('platform');

        return [
            'platform_id' => ['nullable', 'integer', 'exists:social_platforms,id'],
            'name' => ['required', 'string', 'max:80'],
            'slug' => [
                'nullable', 'string', 'max:80', 'alpha_dash',
                Rule::unique('social_design_types', 'slug')
                    ->where(fn ($query) => $query->where('platform_id', $platform?->id ?? $this->input('platform_id')))
                    ->ignore($designType?->id),
            ],
            'width' => ['required', 'integer', 'min:64', 'max:8000'],
            'height' => ['required', 'integer', 'min:64', 'max:8000'],
            'aspect_ratio' => ['nullable', 'string', 'max:16'],
            'safe_area' => ['nullable', 'array'],
            'safe_area.top' => ['nullable', 'numeric', 'min:0', 'max:0.45'],
            'safe_area.right' => ['nullable', 'numeric', 'min:0', 'max:0.45'],
            'safe_area.bottom' => ['nullable', 'numeric', 'min:0', 'max:0.45'],
            'safe_area.left' => ['nullable', 'numeric', 'min:0', 'max:0.45'],
            'export_formats' => ['nullable', 'array', 'max:6'],
            'export_formats.*' => ['string', 'in:png,jpg,webp,pdf,svg'],
            'layout_family' => ['nullable', 'string', 'in:social,story,banner,card'],
            'variant' => ['nullable', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }
}
