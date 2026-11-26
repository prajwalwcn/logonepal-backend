<?php

namespace App\Services\Logo;

use App\Models\AiGenerationLog;
use App\Services\Ai\AiManager;
use App\Services\Ai\LlmClient;
use App\Support\Color;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Produces structured brand intelligence for a single business name.
 *
 * The LLM path is strictly optional: every failure mode (unconfigured,
 * rate-limited, malformed JSON, schema violation) degrades to a deterministic
 * keyword-based analysis, so logo generation never depends on an API key.
 */
class BrandAnalysisService
{
    private const CACHE_VERSION = 'v3';

    public function __construct(private readonly AiManager $ai)
    {
    }

    public function analyze(string $brandName, bool $useCache = true): array
    {
        $brandName = trim(preg_replace('/\s+/u', ' ', $brandName) ?? '');
        $cacheKey = 'brand-intent:'.self::CACHE_VERSION.':'.md5(mb_strtolower($brandName));

        if ($useCache && ($cached = Cache::get($cacheKey))) {
            return $cached;
        }

        $analysis = $this->fromAi($brandName) ?? $this->fallback($brandName);

        Cache::put($cacheKey, $analysis, now()->addDay());

        return $analysis;
    }

    /**
     * Deterministic analysis. Never throws, never depends on external services.
     */
    public function fallback(string $brandName): array
    {
        $industries = config('industries', []);
        $tokens = $this->tokens($brandName);
        $scores = [];

        foreach ($industries as $industry) {
            $haystack = array_merge(
                [$industry['name'], $industry['slug'], $industry['tagline']],
                $industry['keywords'],
            );

            $haystack = array_map(fn ($value) => mb_strtolower((string) $value), $haystack);
            $score = 0;

            foreach ($tokens as $token) {
                $tokenStem = $this->stem($token);

                foreach ($haystack as $term) {
                    $termStem = $this->stem($term);

                    if ($term === $token || ($tokenStem !== '' && $tokenStem === $termStem)) {
                        $score += 4;
                    } elseif (strlen($token) >= 4 && strlen($term) >= 4 && (str_contains($term, $token) || str_contains($token, $term))) {
                        $score += 1;
                    }
                }
            }

            $scores[$industry['slug']] = $score;
        }

        arsort($scores);
        $topSlug = array_key_first($scores);
        $topScore = (int) ($scores[$topSlug] ?? 0);

        // Below three points the match comes from a partial substring (for
        // example "nova" inside "innovation"), which is too weak to assume an
        // industry for. Those names get several visual directions instead.
        $ambiguous = $topScore < 3;

        if ($ambiguous) {
            return $this->ambiguousAnalysis($brandName, $tokens);
        }

        $profile = collect($industries)->firstWhere('slug', $topSlug);

        $palettes = array_merge(
            $profile['palettes'],
            $this->complementaryPalettes($profile['palettes'][0] ?? ['#111827', $profile['accent'], '#E5E7EB']),
        );

        $styles = array_values(array_unique(array_merge(
            array_slice($profile['styles'], 0, 2),
            ['minimal', 'modern', 'geometric', 'bold'],
        )));

        return [
            'brand_name' => mb_strtoupper($brandName),
            'industry' => $profile['name'],
            'category_slug' => $profile['slug'],
            'personality' => $profile['personality'],
            'keywords' => array_values(array_unique(array_merge($tokens, array_slice($profile['keywords'], 0, 4)))),
            'logo_concepts' => $this->concepts($brandName, $profile['symbol_hints'], $profile['name']),
            'styles' => array_slice($styles, 0, 5),
            'color_palettes' => array_slice($palettes, 0, 4),
            'typography_direction' => $this->typographyFor($profile['styles'][0] ?? 'modern'),
            'suggested_layout_orientation' => $this->layoutOrientation($brandName),
            'symbol_hints' => $profile['symbol_hints'],
            'confidence' => min(0.95, 0.55 + $topScore * 0.05),
            'source' => 'fallback',
        ];
    }

    private function ambiguousAnalysis(string $brandName, array $tokens): array
    {
        $directions = [
            ['industry' => 'Consulting', 'slug' => 'consulting', 'styles' => ['minimal', 'corporate'], 'palettes' => [['#0F172A', '#334155', '#94A3B8'], ['#111827', '#1D4ED8', '#C7D2FE']]],
            ['industry' => 'Technology', 'slug' => 'technology', 'styles' => ['modern', 'geometric'], 'palettes' => [['#0F172A', '#2563EB', '#06B6D4'], ['#0B0B14', '#7C3AED', '#22D3EE']]],
            ['industry' => 'Marketing', 'slug' => 'marketing', 'styles' => ['bold', 'playful'], 'palettes' => [['#111827', '#E11D48', '#FDA4AF'], ['#1C1917', '#F59E0B', '#FEF3C7']]],
        ];

        $palettes = [];

        foreach ($directions as $direction) {
            foreach ($direction['palettes'] as $palette) {
                $palettes[] = $palette;
            }
        }

        return [
            'brand_name' => mb_strtoupper($brandName),
            'industry' => 'General',
            'category_slug' => 'general',
            'personality' => ['confident', 'versatile', 'modern'],
            'keywords' => array_values(array_unique(array_merge($tokens, ['brand', 'identity', 'design']))),
            'logo_concepts' => $this->concepts($brandName, ['monogram', 'abstract', 'orbit', 'spark'], 'brand'),
            'styles' => ['minimal', 'modern', 'geometric', 'bold', 'luxury'],
            'color_palettes' => array_slice($palettes, 0, 4),
            'typography_direction' => 'geometric sans serif',
            'suggested_layout_orientation' => $this->layoutOrientation($brandName),
            'symbol_hints' => ['monogram', 'abstract', 'orbit', 'spark'],
            'confidence' => 0.4,
            'source' => 'fallback',
            'directions' => $directions,
        ];
    }

    private function fromAi(string $brandName): ?array
    {
        $client = $this->ai->client();

        if (! $client instanceof LlmClient) {
            return null;
        }

        $started = microtime(true);

        try {
            $industries = collect(config('industries', []))
                ->map(fn (array $industry) => $industry['slug'].' ('.$industry['name'].')')
                ->implode(', ');

            $system = 'You are a brand strategist for a logo design platform. '
                .'Return ONLY JSON matching this schema: '
                .'{"brand_name":string,"industry":string,"category_slug":string,'
                .'"personality":string[3],"keywords":string[5],"logo_concepts":string[5],'
                .'"styles":string[5],"color_palettes":array of 3 arrays of 3 hex strings,'
                .'"typography_direction":string,"suggested_layout_orientation":string}. '
                .'Valid styles: minimal, modern, geometric, luxury, vintage, retro, corporate, playful, '
                .'elegant, bold, futuristic, tech, organic, abstract, monogram, mascot, badge. '
                .'Pick one category_slug from: '.$industries.'. '
                .'The first colour of every palette must be a dark or light ink with strong contrast '
                .'against white. Do not use emoji.';

            $response = $client->chat([
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => 'Business name: '.$brandName],
            ], ['temperature' => 0.6]);

            $decoded = $this->decodeJson($response['content']);

            if ($decoded === null) {
                throw new \RuntimeException('AI response was not valid JSON.');
            }

            $normalized = $this->validateAndNormalize($brandName, $decoded);

            $this->logAi($client, 'brand_intent', $response, microtime(true) - $started, 'success');

            return $normalized;
        } catch (Throwable $exception) {
            $this->logAi($client, 'brand_intent', null, microtime(true) - $started, 'failed', $exception->getMessage());
            Log::info('Brand analysis fell back to deterministic mode.', ['error' => $exception->getMessage()]);

            return null;
        }
    }

    private function validateAndNormalize(string $brandName, array $data): array
    {
        $validator = Validator::make($data, [
            'industry' => ['required', 'string', 'max:80'],
            'category_slug' => ['nullable', 'string', 'max:80'],
            'personality' => ['nullable', 'array'],
            'personality.*' => ['string', 'max:40'],
            'keywords' => ['nullable', 'array'],
            'keywords.*' => ['string', 'max:40'],
            'logo_concepts' => ['nullable', 'array'],
            'logo_concepts.*' => ['string', 'max:80'],
            'styles' => ['nullable', 'array'],
            'styles.*' => ['string', 'max:40'],
            'color_palettes' => ['nullable', 'array', 'min:1'],
            'color_palettes.*' => ['array', 'min:2', 'max:4'],
            'color_palettes.*.*' => ['string', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'typography_direction' => ['nullable', 'string', 'max:80'],
            'suggested_layout_orientation' => ['nullable', 'string', 'max:40'],
        ]);

        if ($validator->fails()) {
            throw new \RuntimeException('AI response failed schema validation: '.$validator->errors()->first());
        }

        $knownStyles = ['minimal', 'modern', 'geometric', 'luxury', 'vintage', 'retro', 'corporate',
            'playful', 'elegant', 'bold', 'futuristic', 'tech', 'organic', 'abstract', 'monogram',
            'mascot', 'badge'];

        $styles = collect($data['styles'] ?? [])
            ->map(fn ($style) => mb_strtolower(trim((string) $style)))
            ->filter(fn ($style) => in_array($style, $knownStyles, true))
            ->unique()
            ->values()
            ->all();

        if ($styles === []) {
            $styles = ['modern', 'minimal', 'geometric'];
        }

        $palettes = collect($data['color_palettes'] ?? [])
            ->map(fn ($palette) => array_values(array_map(
                fn ($hex) => strtoupper(trim((string) $hex)),
                (array) $palette
            )))
            ->filter(fn ($palette) => count($palette) >= 2)
            ->values()
            ->all();

        if ($palettes === []) {
            $fallback = $this->fallback($brandName);
            $palettes = $fallback['color_palettes'];
        }

        $fallbackProfile = $this->fallback($brandName);

        return [
            'brand_name' => mb_strtoupper($brandName),
            'industry' => (string) $data['industry'],
            'category_slug' => $this->normalizeCategorySlug($data['category_slug'] ?? null, $fallbackProfile['category_slug']),
            'personality' => array_slice(array_values((array) ($data['personality'] ?? ['modern'])), 0, 4),
            'keywords' => array_slice(array_values((array) ($data['keywords'] ?? [])), 0, 8)
                ?: $fallbackProfile['keywords'],
            'logo_concepts' => array_slice(array_values((array) ($data['logo_concepts'] ?? [])), 0, 6)
                ?: $fallbackProfile['logo_concepts'],
            'styles' => array_slice($styles, 0, 5),
            'color_palettes' => array_slice($palettes, 0, 4),
            'typography_direction' => (string) ($data['typography_direction'] ?? 'geometric sans serif'),
            'suggested_layout_orientation' => (string) ($data['suggested_layout_orientation'] ?? $this->layoutOrientation($brandName)),
            'symbol_hints' => $fallbackProfile['symbol_hints'],
            'confidence' => 0.9,
            'source' => 'ai',
        ];
    }

    private function normalizeCategorySlug(?string $slug, string $default): string
    {
        if ($slug === null) {
            return $default;
        }

        $slug = mb_strtolower(trim($slug));
        $slugs = collect(config('industries', []))->pluck('slug')->all();

        return in_array($slug, $slugs, true) ? $slug : $default;
    }

    private function decodeJson(string $content): ?array
    {
        $content = trim($content);

        if (preg_match('/```(?:json)?\s*(.+?)```/s', $content, $matches)) {
            $content = trim($matches[1]);
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function logAi(LlmClient $client, string $purpose, ?array $response, float $seconds, string $status, ?string $error = null): void
    {
        try {
            AiGenerationLog::create([
                'provider' => $response['provider'] ?? 'openai',
                'purpose' => $purpose,
                'model' => $response['model'] ?? null,
                'status' => $status,
                'tokens_in' => $response['tokens_in'] ?? null,
                'tokens_out' => $response['tokens_out'] ?? null,
                'latency_ms' => (int) round($seconds * 1000),
                'error' => $error,
            ]);
        } catch (Throwable) {
            // Usage logging must never break a generation request.
        }
    }

    /**
     * @return array<int, string>
     */
    private function tokens(string $brandName): array
    {
        $stopWords = ['the', 'and', 'co', 'inc', 'llc', 'ltd', 'studio', 'group', 'company', 'official', 'hq'];

        $parts = preg_split('/[^a-zA-Z0-9]+/', mb_strtolower($brandName)) ?: [];

        return array_values(array_unique(array_filter(
            $parts,
            fn (string $token) => mb_strlen($token) > 2 && ! in_array($token, $stopWords, true)
        )));
    }

    private function stem(string $word): string
    {
        $word = mb_strtolower(trim($word));

        if (str_ends_with($word, 'ies') && strlen($word) > 4) {
            return substr($word, 0, -3).'y';
        }

        if (str_ends_with($word, 'ches') || str_ends_with($word, 'shes') || str_ends_with($word, 'xes') || str_ends_with($word, 'ses')) {
            return substr($word, 0, -2);
        }

        if (str_ends_with($word, 's') && ! str_ends_with($word, 'ss') && strlen($word) > 3) {
            return substr($word, 0, -1);
        }

        return $word;
    }

    /**
     * @param  array<int, string>  $hints
     * @return array<int, string>
     */
    private function concepts(string $brandName, array $hints, string $industry): array
    {
        $initial = mb_substr(preg_replace('/[^A-Za-z]/', '', $brandName) ?: 'A', 0, 1);
        $hints = $hints ?: ['monogram', 'orbit', 'abstract', 'spark'];

        return [
            'abstract '.mb_strtoupper($initial),
            $hints[0].' mark',
            $hints[1] ?? 'geometric connection',
            mb_strtolower($industry).' emblem',
            'minimal wordmark',
        ];
    }

    /**
     * @param  array<int, string>  $base
     * @return array<int, array<int, string>>
     */
    private function complementaryPalettes(array $base): array
    {
        $ink = $base[0] ?? '#111827';
        $accent = $base[1] ?? '#2563EB';

        return [
            [$ink, $accent, Color::shade($accent, 0.55)],
            [Color::shade($ink, 0.25), $accent, Color::shade($ink, 0.7)],
            [$ink, Color::shade($accent, -0.2), Color::shade($accent, 0.35)],
        ];
    }

    private function typographyFor(string $style): string
    {
        return match ($style) {
            'luxury', 'elegant', 'vintage' => 'high-contrast serif',
            'tech', 'futuristic', 'geometric' => 'geometric sans serif',
            'playful', 'organic' => 'rounded sans serif',
            'corporate' => 'humanist sans serif',
            'bold' => 'heavy grotesque',
            default => 'modern sans serif',
        };
    }

    private function layoutOrientation(string $brandName): string
    {
        $length = mb_strlen($brandName);

        return match (true) {
            $length <= 9 => '1-line-horizontal',
            $length <= 16 => '1-line-horizontal',
            default => '2-line-stacked',
        };
    }
}
