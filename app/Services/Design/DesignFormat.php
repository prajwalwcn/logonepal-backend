<?php

namespace App\Services\Design;

/**
 * A single entry in the design-format catalogue: one generator, its canvas,
 * its layout family and the copy fields the studio asks the user for.
 */
final class DesignFormat
{
    /**
     * @param  array<int, string>  $fieldKeys
     * @param  array<string, string>  $defaults
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly string $category,
        public readonly int $width,
        public readonly int $height,
        public readonly string $layout,
        public readonly string $variant,
        public readonly array $fieldKeys,
        public readonly array $defaults,
    ) {
    }

    /**
     * Field definitions with labels, input types and default copy resolved.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fields(): array
    {
        $fields = [];

        foreach (array_unique(array_merge(['brand'], $this->fieldKeys)) as $key) {
            $definition = DesignFormats::fieldDefinition($key);

            if ($definition === null) {
                continue;
            }

            $fields[] = [
                'key' => $key,
                'label' => $definition['label'],
                'type' => $definition['type'],
                'placeholder' => $definition['placeholder'],
                'max' => $definition['max'],
                'required' => $key === 'brand',
                'default' => $this->defaults[$key] ?? DesignFormats::FIELD_DEFAULTS[$key] ?? '',
            ];
        }

        return $fields;
    }

    public function aspect(): string
    {
        if ($this->width === $this->height) {
            return 'square';
        }

        return $this->width > $this->height ? 'landscape' : 'portrait';
    }

    /**
     * Compact representation used by the catalogue hub. It deliberately omits
     * field schemas, descriptions and palette metadata so the index stays fast.
     *
     * @return array<string, mixed>
     */
    public function toSummaryArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'category' => $this->category,
            'width' => $this->width,
            'height' => $this->height,
            'aspect' => $this->aspect(),
            'layout' => $this->layout,
            'variant' => $this->variant,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge($this->toSummaryArray(), [
            'category_label' => DesignFormats::CATEGORIES[$this->category]['label'] ?? ucfirst($this->category),
            'description' => DesignFormats::describe($this->name, $this->category),
            'fields' => $this->fields(),
            'suggested_colors' => DesignFormats::suggestedColors($this->category),
            'suggested_font' => DesignFormats::suggestedFont($this->category),
        ]);
    }
}
