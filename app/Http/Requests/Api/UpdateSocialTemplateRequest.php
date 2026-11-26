<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSocialTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $template = $this->route('template');

        return [
            'name' => ['sometimes', 'string', 'max:140'],
            'brand' => ['nullable', 'string', 'max:80'],
            'headline' => ['nullable', 'string', 'max:140'],
            'subhead' => ['nullable', 'string', 'max:200'],
            'cta' => ['nullable', 'string', 'max:40'],
            'handle' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'string', 'max:100'],
            'style' => ['nullable', 'string', 'max:40'],
            'font' => ['nullable', 'string', 'max:80'],
            'slug' => ['sometimes', 'string', 'max:160', 'alpha_dash', Rule::unique('social_templates', 'slug')->ignore($template?->id)],
            'description' => ['nullable', 'string', 'max:1200'],
            'design_type_id' => ['sometimes', 'integer', 'exists:social_design_types,id'],
            'industry_id' => ['nullable', 'integer', 'exists:industries,id'],
            'category_id' => ['nullable', 'integer', 'exists:template_categories,id'],
            'styles' => ['nullable', 'array', 'max:8'],
            'styles.*' => ['string', 'max:40'],
            'keywords' => ['nullable', 'array', 'max:20'],
            'keywords.*' => ['string', 'max:60'],
            'tags' => ['nullable', 'array', 'max:20'],
            'tags.*' => ['string', 'max:40'],
            'palette' => ['nullable', 'array', 'max:6'],
            'palette.*' => ['string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'fonts' => ['nullable', 'array', 'max:4'],
            'fonts.*' => ['string', 'max:80'],
            'design_json' => ['sometimes', 'array'],
            'layer_config' => ['nullable', 'array'],
            'asset_refs' => ['nullable', 'array'],
            'license_type' => ['nullable', 'string', 'max:32'],
            'is_premium' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'is_trending' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'in:draft,published,archived'],
            'changelog' => ['nullable', 'string', 'max:500'],
        ];
    }
}
