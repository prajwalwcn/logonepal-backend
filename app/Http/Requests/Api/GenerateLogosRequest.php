<?php

namespace App\Http\Requests\Api;

use App\Services\Logo\Layouts;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateLogosRequest extends FormRequest
{
    private const STYLES = [
        'minimal', 'modern', 'geometric', 'luxury', 'vintage', 'retro', 'corporate',
        'playful', 'elegant', 'bold', 'futuristic', 'tech', 'organic', 'abstract',
        'monogram', 'mascot', 'badge', 'classic',
    ];

    private const COLORS = [
        'grayscale', 'black', 'blue', 'green', 'red', 'orange', 'purple', 'pink',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('business_name');

        if (is_string($name)) {
            $this->merge(['business_name' => trim(preg_replace('/\s+/u', ' ', $name) ?? '')]);
        }

        $keywords = $this->input('keywords');

        if (is_string($keywords)) {
            $this->merge(['keywords' => trim(preg_replace('/\s+/u', ' ', $keywords) ?? '')]);
        }
    }

    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'min:2', 'max:120', 'regex:/^[\pL\pN\s&\'’.,!\-+]+$/u'],
            'keywords' => ['nullable', 'string', 'max:160'],
            'tagline' => ['nullable', 'string', 'max:80'],
            'style' => ['nullable', 'string', Rule::in(self::STYLES)],
            'styles' => ['nullable', 'array', 'max:12'],
            'styles.*' => ['string', Rule::in(self::STYLES)],
            'layout' => ['nullable', 'string', Rule::in(Layouts::slugs())],
            'color' => ['nullable', 'string', Rule::in(self::COLORS)],
            'colors' => ['nullable', 'array', 'max:10'],
            'colors.*' => ['string', Rule::in(self::COLORS)],
            'premium' => ['nullable', 'string', Rule::in(['free', 'premium'])],
            'background' => ['nullable', 'string', Rule::in(['auto', 'transparent', 'light', 'dark'])],
            'sort' => ['nullable', 'string', Rule::in(['recommended', 'popular', 'newest', 'minimal', 'premium'])],
            'page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:8', 'max:48'],
        ];
    }

    public function messages(): array
    {
        return [
            'business_name.regex' => 'Use letters, numbers, spaces and common punctuation only.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        $styles = array_values(array_unique(array_filter(
            (array) $this->input('styles', []),
            fn ($value) => is_string($value) && $value !== ''
        )));
        sort($styles);

        $colors = array_values(array_unique(array_filter(
            (array) $this->input('colors', []),
            fn ($value) => is_string($value) && $value !== ''
        )));
        sort($colors);

        $filters = array_filter([
            'keywords' => $this->input('keywords'),
            'style' => $this->input('style'),
            'styles' => $styles,
            'layout' => $this->input('layout'),
            'color' => $this->input('color'),
            'colors' => $colors,
            'premium' => $this->input('premium'),
            'background' => $this->input('background'),
            'tagline' => $this->input('tagline'),
        ], fn ($value) => $value !== null && $value !== '' && $value !== []);

        ksort($filters);

        return $filters;
    }
}
