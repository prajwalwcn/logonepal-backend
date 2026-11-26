<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'format' => ['required', 'string', Rule::in(['svg', 'png', 'png-hd', 'pdf', 'zip'])],
            'variant' => ['sometimes', 'string', Rule::in([
                'full', 'transparent', 'black', 'white', 'icon', 'horizontal', 'vertical',
            ])],
            'quality' => ['sometimes', 'string', Rule::in(['standard', 'high', 'print'])],
        ];
    }
}
