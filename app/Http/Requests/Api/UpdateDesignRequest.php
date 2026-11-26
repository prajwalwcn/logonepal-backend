<?php

namespace App\Http\Requests\Api;

use App\Services\Logo\DesignDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'business_name' => ['sometimes', 'string', 'max:60'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'design_json' => ['sometimes', 'array'],
            'design_json.version' => ['required_with:design_json', 'integer'],
            'design_json.canvas' => ['required_with:design_json', 'array'],
            'design_json.canvas.width' => ['required_with:design_json', 'numeric', 'min:100', 'max:4000'],
            'design_json.canvas.height' => ['required_with:design_json', 'numeric', 'min:100', 'max:4000'],
            'design_json.objects' => ['required_with:design_json', 'array', 'max:200'],
            'design_json.objects.*.type' => ['required', 'string'],
            'design_json.objects.*.role' => ['required', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->has('design_json')) {
                return;
            }

            $document = new DesignDocument((array) $this->input('design_json'));

            foreach ($document->validate() as $error) {
                $validator->errors()->add('design_json', $error);
            }
        });
    }
}
