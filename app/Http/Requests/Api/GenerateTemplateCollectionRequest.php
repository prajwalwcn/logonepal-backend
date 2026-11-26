<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class GenerateTemplateCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand' => ['required', 'string', 'min:2', 'max:80'],
            'industry_id' => ['nullable', 'integer', 'exists:industries,id'],
            'palette' => ['nullable', 'array', 'max:6'],
            'palette.*' => ['string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'font' => ['nullable', 'string', 'max:80'],
            'style' => ['nullable', 'string', 'max:40'],
            'design_types' => ['nullable', 'array', 'max:12'],
            'design_types.*' => ['string', 'exists:social_design_types,slug'],
        ];
    }
}
