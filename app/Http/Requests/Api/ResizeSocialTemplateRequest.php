<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ResizeSocialTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'template_uuid' => ['nullable', 'string', 'exists:social_templates,uuid'],
            'design_json' => ['nullable', 'array', 'required_without:template_uuid'],
            'target_design_type' => ['required', 'string', 'exists:social_design_types,slug'],
            'brand' => ['nullable', 'string', 'max:80'],
        ];
    }
}
