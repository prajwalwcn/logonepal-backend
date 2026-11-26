<?php

namespace App\Services\Logo;

use App\Models\GeneratedLogo;
use App\Models\LogoAsset;
use App\Models\LogoGeneration;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LogoGenerationService
{
    /** Candidates computed and stored per generation so "load more" and pagination need no recompute. */
    public const STORED_RESULTS = 320;

    public function __construct(
        private readonly BrandAnalysisService $analysis,
        private readonly VariationEngineService $engine,
        private readonly SvgRenderingService $renderer,
        private readonly KeywordNormalizer $normalizer,
        private readonly TemplateMatchingService $matcher,
    ) {
    }

    /**
     * Generate (or reuse) a gallery for a business name.
     */
    public function generate(
        string $brandName,
        array $filters = [],
        ?User $user = null,
        ?string $guestToken = null,
        ?string $ip = null,
    ): LogoGeneration {
        $parsed = $this->normalizer->parseInput(
            $brandName,
            is_string($filters['keywords'] ?? null) ? $filters['keywords'] : null,
        );
        $cleanBrandName = $parsed['business_name'] ?: trim(preg_replace('/\s+/u', ' ', $brandName) ?? '');

        $filters = array_filter($filters, fn ($value) => $value !== null && $value !== '' && $value !== []);
        $resolved = [
            'terms' => [],
            'corrections' => [],
            'inferred_styles' => [],
            'inferred_colors' => [],
        ];

        if ($parsed['raw_tokens'] !== []) {
            $resolved = $this->normalizer->resolve($parsed['raw_tokens'], $this->symbolVocabulary());
            $finalKeywords = $resolved['terms'] !== [] ? $resolved['terms'] : $parsed['raw_tokens'];
            $filters['keywords'] = implode(', ', $finalKeywords);

            if ($resolved['inferred_styles'] !== [] && empty($filters['styles']) && empty($filters['style'])) {
                $filters['styles'] = $resolved['inferred_styles'];
            }

            if ($resolved['inferred_colors'] !== [] && empty($filters['colors']) && empty($filters['color'])) {
                $filters['colors'] = $resolved['inferred_colors'];
            }
        }

        ksort($filters);

        $existing = $this->reusableGeneration($cleanBrandName, $filters, $user, $guestToken);

        if ($existing !== null) {
            return $existing;
        }

        $intent = $this->analysis->analyze($cleanBrandName);
        $intent['resolved_keywords'] = $resolved['terms'];
        $intent['keyword_corrections'] = $resolved['corrections'];

        $matched = $this->matcher->matchSymbols($intent, $filters);
        $intent['matched_symbol_count'] = $matched['direct_count'];

        $candidates = $this->engine->candidates($intent, $cleanBrandName, $filters, self::STORED_RESULTS);

        $generation = DB::transaction(function () use ($cleanBrandName, $filters, $intent, $candidates, $user, $guestToken, $ip) {
            $generation = LogoGeneration::create([
                'business_name' => $cleanBrandName,
                'normalized_name' => mb_strtolower($cleanBrandName),
                'engine' => 'template',
                'status' => 'ready',
                'intent' => $intent,
                'filters' => $filters,
                'total_count' => count($candidates),
                'user_id' => $user?->id,
                'guest_token' => $guestToken,
                'ip_address' => $ip,
            ]);

            $now = now();
            $rows = [];

            foreach ($candidates as $position => $candidate) {
                $hasSolidBg = (($candidate['design']['objects'][0]['fill']['type'] ?? 'none') === 'solid');
                $svg = $this->renderer->sanitize($this->renderer->render($candidate['design'], ['crop' => ! $hasSolidBg]));

                $rows[] = [
                    'generation_id' => $generation->id,
                    'template_id' => null,
                    'symbol_asset_id' => $candidate['symbol']?->id,
                    'font_id' => $candidate['font']->id,
                    'style_slug' => $candidate['style'],
                    'layout' => $candidate['layout'],
                    'orientation' => Layouts::orientation($candidate['layout']),
                    'palette' => json_encode($candidate['palette']->colors ?? []),
                    'design_json' => json_encode($candidate['design']),
                    'svg' => $svg,
                    'score' => $candidate['score'],
                    'position' => $position,
                    'is_premium' => $candidate['is_premium'],
                    'is_ai_symbol' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 100) as $chunk) {
                GeneratedLogo::insert($chunk);
            }

            return $generation;
        });

        return $generation->load('logos');
    }

    public function page(LogoGeneration $generation, int $page = 1, int $perPage = 24, ?string $sort = null): LengthAwarePaginator
    {
        $query = $generation->logos()->with(['font', 'symbolAsset']);

        $query = match ($sort) {
            'minimal' => $query->orderByRaw("FIELD(layout, 'wordmark','text-only','monogram','icon-left') DESC")->orderByDesc('score'),
            'popular' => $query->orderByDesc('score'),
            'newest' => $query->orderByDesc('id'),
            'premium' => $query->orderByDesc('is_premium')->orderByDesc('score'),
            default => $query->orderBy('position'),
        };

        return $query->paginate(perPage: $perPage, page: $page);
    }

    private function reusableGeneration(string $brandName, array $filters, ?User $user, ?string $guestToken): ?LogoGeneration
    {
        if ($user === null && $guestToken === null) {
            return null;
        }

        $query = LogoGeneration::query()
            ->where('normalized_name', mb_strtolower($brandName))
            ->where('status', 'ready')
            ->where('created_at', '>', now()->subHours(6))
            ->where('filters', json_encode($filters ?: new \stdClass()));

        if ($user !== null) {
            $query->where('user_id', $user->id);
        } else {
            $query->where('guest_token', $guestToken);
        }

        $generation = $query->latest('id')->first();

        return $generation?->load('logos');
    }

    /**
     * Collect searchable keyword vocabulary across active symbols (cached to DB/file store).
     *
     * @return array<int, string>
     */
    private function symbolVocabulary(): array
    {
        return Cache::remember('logoforge:symbol-vocab:v2', now()->addMinutes(10), function () {
            $tokens = [];

            foreach (LogoAsset::query()->active()->get(['name', 'slug', 'keywords']) as $asset) {
                foreach ($asset->keywords ?? [] as $kw) {
                    $clean = mb_strtolower(trim((string) $kw));
                    if (mb_strlen($clean) >= 3) {
                        $tokens[$clean] = true;
                    }
                }

                foreach (preg_split('/[^a-z0-9]+/i', (string) $asset->slug) ?: [] as $part) {
                    $clean = mb_strtolower($part);
                    if (mb_strlen($clean) >= 3 && ! in_array($clean, ['mdi', 'duotone', 'fill', 'outline', 'variant'], true)) {
                        $tokens[$clean] = true;
                    }
                }

                foreach (preg_split('/[^a-z0-9]+/i', (string) $asset->name) ?: [] as $part) {
                    $clean = mb_strtolower($part);
                    if (mb_strlen($clean) >= 3) {
                        $tokens[$clean] = true;
                    }
                }
            }

            return array_keys($tokens);
        });
    }
}
