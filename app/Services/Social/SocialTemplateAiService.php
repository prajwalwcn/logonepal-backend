<?php

namespace App\Services\Social;

use App\Services\Ai\AiManager;
use App\Services\Ai\LlmClient;
use App\Services\Design\DesignFormats;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * AI assistance for template art direction.
 *
 * AI never produces flattened designs: it only returns structured suggestions
 * (palettes, type, style, keywords, layout direction) and optional raster
 * assets. When the provider is unavailable the deterministic studio rules take
 * over, so the admin workflow never blocks on a third party.
 */
class SocialTemplateAiService
{
    public function __construct(private readonly AiManager $ai)
    {
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function suggest(array $context): array
    {
        $client = $this->ai->client();

        if (! $client instanceof LlmClient || ! $client->isConfigured()) {
            return $this->fallback($context);
        }

        try {
            $system = 'You are a senior social-media art director. Return ONLY JSON with this schema: '
                .'{"palettes":[[hex,hex,hex],[hex,hex,hex],[hex,hex,hex]],'
                .'"fonts":["family","family","family"],'
                .'"styles":["style","style","style"],'
                .'"layout":"short direction",'
                .'"keywords":["keyword"],'
                .'"description":"one sentence"}. '
                .'Palettes must have strong contrast and be production-ready. '
                .'Use only these styles: minimal, modern, luxury, corporate, elegant, creative, bold, colorful, gradient, retro, vintage, editorial, geometric, futuristic, 3d, abstract, professional, playful, dark, light.';

            $response = $client->chat([
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => json_encode($context, JSON_UNESCAPED_SLASHES) ?: '{}'],
            ], ['temperature' => 0.6, 'max_tokens' => 700]);

            $decoded = $this->decode($response['content']);

            if ($decoded === null) {
                return $this->fallback($context);
            }

            $palettes = $this->validPalettes($decoded['palettes'] ?? []);
            $fonts = array_values(array_filter((array) ($decoded['fonts'] ?? []), 'is_string'));
            $styles = array_values(array_filter((array) ($decoded['styles'] ?? []), 'is_string'));

            if ($palettes === []) {
                return $this->fallback($context);
            }

            return [
                'source' => 'ai',
                'palettes' => $palettes,
                'fonts' => array_slice($fonts, 0, 4),
                'styles' => array_slice($styles, 0, 5),
                'layout' => (string) ($decoded['layout'] ?? ''),
                'keywords' => array_slice(array_values(array_filter((array) ($decoded['keywords'] ?? []), 'is_string')), 0, 8),
                'description' => (string) ($decoded['description'] ?? ''),
            ];
        } catch (Throwable $exception) {
            Log::info('Social template AI suggestion fell back to deterministic rules.', [
                'error' => $exception->getMessage(),
            ]);

            return $this->fallback($context);
        }
    }

    /**
     * Generate a decorative raster asset (background, illustration, product
     * scene). The returned URL is stored as an asset reference, never baked
     * into text or the editable document.
     *
     * @return array<int, string>
     */
    public function generateAsset(string $prompt): array
    {
        $client = $this->ai->client();

        if (! $client instanceof LlmClient || ! $client->isConfigured()) {
            return [];
        }

        try {
            $result = $client->generateImage($prompt, ['size' => '1024x1024']);

            return array_values(array_filter($result['urls'] ?? [], 'is_string'));
        } catch (Throwable $exception) {
            Log::info('Social template AI image generation failed.', ['error' => $exception->getMessage()]);

            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function fallback(array $context): array
    {
        $category = (string) ($context['category'] ?? 'social');
        $base = DesignFormats::suggestedColors($category);
        $font = DesignFormats::suggestedFont($category);
        $industry = (string) ($context['industry'] ?? 'business');

        return [
            'source' => 'fallback',
            'palettes' => [
                $base,
                [$base[0], '#FFFFFF', $base[1]],
                ['#0F172A', $base[1], '#E2E8F0'],
            ],
            'fonts' => [$font, 'Poppins', 'Inter'],
            'styles' => ['modern', 'minimal', 'editorial'],
            'layout' => 'Headline-led composition with the mark above the type and one accent shape.',
            'keywords' => array_values(array_unique([$industry, 'social media', 'brand', 'template'])),
            'description' => 'A clean, platform-ready layout with a strong headline and generous safe margins.',
        ];
    }

    /**
     * @param  array<int, mixed>  $palettes
     * @return array<int, array<int, string>>
     */
    private function validPalettes(array $palettes): array
    {
        $valid = [];

        foreach ($palettes as $palette) {
            if (! is_array($palette)) {
                continue;
            }

            $colors = array_values(array_filter($palette, fn ($color) => is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color)));

            if (count($colors) >= 3) {
                $valid[] = array_slice($colors, 0, 3);
            }
        }

        return array_slice($valid, 0, 4);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decode(string $content): ?array
    {
        $content = trim($content);

        if (preg_match('/```(?:json)?\s*(.+?)```/s', $content, $matches)) {
            $content = trim($matches[1]);
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }
}
