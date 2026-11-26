<?php

namespace App\Services\Logo;

use App\Models\AiGenerationLog;
use App\Models\Font;
use App\Models\LogoAsset;
use App\Services\Ai\AiManager;
use Throwable;

/**
 * Translates a natural-language edit into structured design changes.
 *
 * Runs an LLM when configured, and otherwise falls back to a deterministic
 * keyword interpreter covering the most requested edits (colour, typeface,
 * weight, case). The editor stays useful with zero AI credentials.
 */
class AiEditorAssistantService
{
    private const COLOR_WORDS = [
        'gold' => 'gold', 'luxury' => 'gold', 'navy' => 'navy', 'blue' => 'blue',
        'green' => 'green', 'red' => 'red', 'purple' => 'purple', 'violet' => 'purple',
        'pink' => 'pink', 'orange' => 'orange', 'teal' => 'teal', 'black' => 'black',
        'monochrome' => 'black',
    ];

    private const PALETTE_MAP = [
        'blue' => '#2563EB', 'navy' => '#1E3A8A', 'green' => '#15803D', 'red' => '#DC2626',
        'purple' => '#7C3AED', 'pink' => '#DB2777', 'orange' => '#EA580C', 'teal' => '#0D9488',
        'gold' => '#A16207', 'black' => '#111827',
    ];

    private const FONT_HINTS = [
        'serif' => 'playfair-display', 'classic' => 'playfair-display', 'elegant' => 'playfair-display',
        'modern' => 'poppins', 'clean' => 'poppins', 'minimal' => 'poppins',
        'bold' => 'anton', 'heavy' => 'anton', 'strong' => 'archivo-black',
        'script' => 'caveat', 'handwritten' => 'caveat', 'playful' => 'dancing-script',
        'tech' => 'space-grotesk', 'futuristic' => 'space-grotesk',
    ];

    public function __construct(
        private readonly AiManager $ai,
        private readonly LogoComposer $composer,
    ) {
    }

    /**
     * @return array{design: array, message: string, applied: array<int, string>}
     */
    public function edit(array $design, string $instruction): array
    {
        $plan = $this->planFromAi($instruction, $design) ?? $this->planLocally($instruction);

        return $this->apply($design, $plan);
    }

    /**
     * @return array{color?: string, font?: string, uppercase?: bool, layout?: string, weight?: int}|null
     */
    private function planFromAi(string $instruction, array $design): ?array
    {
        $client = $this->ai->client();

        if ($client === null) {
            return null;
        }

        try {
            $fonts = Font::query()->active()->pluck('slug')->implode(', ');
            $layouts = implode(', ', Layouts::slugs());

            $response = $client->chat([
                ['role' => 'system', 'content' => 'You convert logo editing requests into JSON with optional keys: '
                    .'color (hex), font (one of: '.$fonts.'), uppercase (boolean), layout (one of: '.$layouts.'), '
                    .'weight (400-800). Return only JSON.'],
                ['role' => 'user', 'content' => $instruction."\nCurrent meta: ".json_encode($design['meta'] ?? [])],
            ], ['temperature' => 0.2, 'max_tokens' => 200]);

            $decoded = json_decode($response['content'], true);

            if (! is_array($decoded)) {
                return null;
            }

            AiGenerationLog::create([
                'provider' => $response['provider'],
                'purpose' => 'editor_assistant',
                'model' => $response['model'],
                'status' => 'success',
                'tokens_in' => $response['tokens_in'],
                'tokens_out' => $response['tokens_out'],
            ]);

            return array_filter([
                'color' => isset($decoded['color']) && preg_match('/^#[0-9a-fA-F]{6}$/', (string) $decoded['color']) ? $decoded['color'] : null,
                'font' => isset($decoded['font']) ? (string) $decoded['font'] : null,
                'uppercase' => isset($decoded['uppercase']) ? (bool) $decoded['uppercase'] : null,
                'layout' => isset($decoded['layout']) && in_array($decoded['layout'], Layouts::slugs(), true) ? $decoded['layout'] : null,
                'weight' => isset($decoded['weight']) ? (int) $decoded['weight'] : null,
            ], fn ($value) => $value !== null);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{color?: string, font?: string, uppercase?: bool, layout?: string, weight?: int}
     */
    private function planLocally(string $instruction): array
    {
        $text = mb_strtolower($instruction);
        $plan = [];

        foreach (self::COLOR_WORDS as $word => $color) {
            if (str_contains($text, $word)) {
                $plan['color'] = self::PALETTE_MAP[$color];
                break;
            }
        }

        foreach (self::FONT_HINTS as $word => $slug) {
            if (str_contains($text, $word)) {
                $plan['font'] = $slug;
                break;
            }
        }

        if (str_contains($text, 'uppercase') || str_contains($text, 'all caps')) {
            $plan['uppercase'] = true;
        }

        if (str_contains($text, 'lowercase') || str_contains($text, 'sentence case')) {
            $plan['uppercase'] = false;
        }

        foreach (Layouts::slugs() as $layout) {
            $needle = str_replace('-', ' ', $layout);

            if (str_contains($text, $needle)) {
                $plan['layout'] = $layout;
                break;
            }
        }

        if (str_contains($text, 'bold')) {
            $plan['weight'] = 800;
        } elseif (str_contains($text, 'light') || str_contains($text, 'thin')) {
            $plan['weight'] = 300;
        }

        return $plan;
    }

    /**
     * @param  array<string, mixed>  $plan
     * @return array{design: array, message: string, applied: array<int, string>}
     */
    private function apply(array $design, array $plan): array
    {
        $applied = [];

        if (isset($plan['layout'])) {
            $design = $this->recompose($design, $plan['layout']);

            if ($design !== []) {
                $applied[] = 'layout';
            }
        }

        if (isset($plan['font'])) {
            $font = Font::query()->where('slug', $plan['font'])->first();

            if ($font !== null) {
                foreach ($design['objects'] as $index => $object) {
                    if (($object['type'] ?? null) === 'i-text') {
                        $design['objects'][$index]['fontFamily'] = $font->family;
                        $design['meta']['font'] = $font->slug;
                    }
                }

                $applied[] = 'font';
            }
        }

        if (isset($plan['weight'])) {
            foreach ($design['objects'] as $index => $object) {
                if (($object['type'] ?? null) === 'i-text' && ($object['role'] ?? '') === 'primary-text') {
                    $design['objects'][$index]['fontWeight'] = $plan['weight'];
                }
            }

            $applied[] = 'weight';
        }

        if (isset($plan['uppercase'])) {
            foreach ($design['objects'] as $index => $object) {
                if (($object['type'] ?? null) === 'i-text') {
                    $text = (string) ($object['text'] ?? '');
                    $design['objects'][$index]['text'] = $plan['uppercase'] ? mb_strtoupper($text) : mb_convert_case(mb_strtolower($text), MB_CASE_TITLE);
                    $design['objects'][$index]['isUppercase'] = (bool) $plan['uppercase'];
                }
            }

            $applied[] = 'case';
        }

        if (isset($plan['color'])) {
            $design = $this->recolor($design, $plan['color']);
            $applied[] = 'color';
        }

        return [
            'design' => $design,
            'applied' => $applied,
            'message' => $applied === []
                ? 'I could not map that request to an edit yet. Try naming a colour, a font style (serif, modern, bold) or a layout.'
                : 'Updated your logo: '.implode(', ', $applied).'.',
        ];
    }

    private function recolor(array $design, string $color): array
    {
        $recolor = function (array $objects) use (&$recolor, $color): array {
            return array_map(function (array $object) use (&$recolor, $color) {
                if (in_array($object['role'] ?? '', ['primary-text'], true)) {
                    $object['fill'] = $color;
                }

                if (! empty($object['objects'])) {
                    $object['objects'] = $recolor($object['objects']);
                }

                return $object;
            }, $objects);
        };

        $design['objects'] = $recolor($design['objects']);
        $design['meta']['palette'] = array_merge([$color], array_slice($design['meta']['palette'] ?? [], 1));

        return $design;
    }

    private function recompose(array $design, string $layout): array
    {
        $meta = $design['meta'] ?? [];
        $font = Font::query()->where('slug', $meta['font'] ?? '')->first();
        $symbol = LogoAsset::query()->where('slug', $meta['symbol'] ?? '')->first();

        if ($font === null) {
            return [];
        }

        $palette = new \App\Models\ColorPalette([
            'name' => 'Current',
            'slug' => 'current',
            'colors' => $meta['palette'] ?? ['#111827', '#2563EB', '#06B6D4'],
            'is_dark' => (bool) ($meta['is_dark'] ?? false),
        ]);

        $primary = null;
        $tagline = null;

        foreach ($design['objects'] as $object) {
            if (($object['role'] ?? null) === 'primary-text') {
                $primary = $object['text'] ?? null;
            }

            if (($object['role'] ?? null) === 'new-text') {
                $tagline = $object['text'] ?? null;
            }
        }

        return $this->composer->compose([
            'brand_name' => (string) ($primary ?: 'Brand'),
            'tagline' => $tagline,
            'symbol' => $symbol,
            'font' => $font,
            'palette' => $palette,
            'layout' => $layout,
            'style' => $meta['style'] ?? 'modern',
        ]);
    }
}
