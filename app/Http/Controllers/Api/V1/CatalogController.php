<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ColorPalette;
use App\Models\Font;
use App\Models\Industry;
use App\Models\LogoAsset;
use App\Models\LogoStyle;
use App\Models\LogoTemplate;
use App\Models\Plan;
use App\Models\SiteSetting;
use App\Services\Logo\Layouts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CatalogController extends Controller
{
    public function templates(Request $request): JsonResponse
    {
        $query = LogoTemplate::query()
            ->with(['industry:id,name,slug', 'style:id,name,slug', 'font:id,slug,family,category', 'symbol:id,slug'])
            ->where('is_active', true);

        if ($industry = $request->query('industry')) {
            $query->whereHas('industry', fn ($q) => $q->where('slug', $industry));
        }

        if ($style = $request->query('style')) {
            $query->whereHas('style', fn ($q) => $q->where('slug', $style));
        }

        if ($layout = $request->query('layout')) {
            $query->where('layout', $layout);
        }

        if ($request->boolean('premium')) {
            $query->where('is_premium', true);
        }

        $templates = $query->orderByDesc('popularity')
            ->paginate(perPage: min(60, (int) $request->query('per_page', 24)));

        return response()->json([
            'data' => collect($templates->items())->map(fn (LogoTemplate $template) => [
                'id' => $template->id,
                'slug' => $template->slug,
                'name' => $template->name,
                'layout' => $template->layout,
                'orientation' => $template->orientation,
                'is_premium' => $template->is_premium,
                'token' => $template->template_token,
                'version' => $template->version,
                'industry' => $template->industry?->name,
                'industry_slug' => $template->industry?->slug,
                'style' => $template->style?->name,
                'font' => $template->font?->family,
                'symbol' => $template->symbol?->slug,
                'svg' => $template->svg,
                'design' => $template->design_json,
            ]),
            'meta' => [
                'current_page' => $templates->currentPage(),
                'last_page' => $templates->lastPage(),
                'total' => $templates->total(),
            ],
        ]);
    }

    public function templateByToken(string $token): JsonResponse
    {
        $template = LogoTemplate::query()
            ->with(['industry', 'style', 'font'])
            ->where('template_token', $token)
            ->firstOrFail();

        return response()->json(['data' => [
            'id' => $template->id,
            'slug' => $template->slug,
            'name' => $template->name,
            'layout' => $template->layout,
            'is_premium' => $template->is_premium,
            'token' => $template->template_token,
            'version' => $template->version,
            'industry' => $template->industry?->name,
            'style' => $template->style?->name,
            'svg' => $template->svg,
            'design' => $template->design_json,
        ]]);
    }

    public function template(LogoTemplate $template): JsonResponse
    {
        $template->load(['industry', 'style', 'font', 'symbol']);

        return response()->json(['data' => [
            'id' => $template->id,
            'slug' => $template->slug,
            'name' => $template->name,
            'layout' => $template->layout,
            'is_premium' => $template->is_premium,
            'token' => $template->template_token,
            'version' => $template->version,
            'industry' => $template->industry?->name,
            'style' => $template->style?->name,
            'svg' => $template->svg,
            'design' => $template->design_json,
        ]]);
    }

    public function industries(): JsonResponse
    {
        $data = Cache::remember('catalog.industries', 600, fn () => Industry::query()->active()->get()->map(fn (Industry $industry) => [
            'id' => $industry->id,
            'name' => $industry->name,
            'slug' => $industry->slug,
            'tagline' => $industry->tagline,
            'description' => $industry->description,
            'accent_color' => $industry->accent_color,
            'keywords' => $industry->keywords,
        ])->values()->all());

        return response()->json(['data' => $data])
            ->header('Cache-Control', 'public, max-age=300');
    }

    public function categories(): JsonResponse
    {
        $data = Cache::remember('catalog.categories', 600, fn () => Category::query()->active()->get(['id', 'name', 'slug'])->toArray());

        return response()->json(['data' => $data])
            ->header('Cache-Control', 'public, max-age=300');
    }

    public function styles(): JsonResponse
    {
        $data = Cache::remember('catalog.styles', 600, fn () => LogoStyle::query()->active()->get(['id', 'name', 'slug'])->toArray());

        return response()->json(['data' => $data])
            ->header('Cache-Control', 'public, max-age=300');
    }

    public function fonts(): JsonResponse
    {
        $data = Cache::remember('catalog.fonts', 600, fn () => Font::query()->active()->get()->map(fn (Font $font) => [
            'id' => $font->id,
            'name' => $font->name,
            'slug' => $font->slug,
            'family' => $font->family,
            'google_family' => $font->google_family,
            'category' => $font->category,
            'weights' => $font->weights,
            'has_italic' => $font->has_italic,
            'preview_text' => $font->preview_text,
            'is_premium' => $font->is_premium,
        ])->values()->all());

        return response()->json(['data' => $data])
            ->header('Cache-Control', 'public, max-age=300');
    }

    public function palettes(): JsonResponse
    {
        $data = Cache::remember('catalog.palettes', 600, fn () => ColorPalette::query()->active()->get()->map(fn (ColorPalette $palette) => [
            'id' => $palette->id,
            'name' => $palette->name,
            'slug' => $palette->slug,
            'colors' => $palette->colors,
            'mood' => $palette->mood,
            'is_dark' => $palette->is_dark,
        ])->values()->all());

        return response()->json(['data' => $data])
            ->header('Cache-Control', 'public, max-age=300');
    }

    public function symbols(Request $request): JsonResponse
    {
        $query = LogoAsset::query()->active();

        if ($category = $request->query('category')) {
            $category = mb_strtolower((string) $category);
            $query->whereJsonContains('keywords', $category);
        }

        if ($search = $request->query('q')) {
            $search = mb_strtolower((string) $search);
            $query->where(function ($scope) use ($search) {
                $scope->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhereJsonContains('keywords', $search);
            });
        }

        return response()->json([
            'data' => $query->get()->map(function (LogoAsset $asset) {
                $keywords = $asset->keywords ?? [];
                $category = 'abstract';

                foreach (['basic', 'badges', 'travel', 'nature', 'animals', 'food', 'tech', 'business', 'lifestyle', 'abstract'] as $known) {
                    if (in_array($known, $keywords, true)) {
                        $category = $known;
                        break;
                    }
                }

                return [
                    'slug' => $asset->slug,
                    'name' => $asset->name,
                    'category' => $category,
                    'asset_type' => $asset->asset_type ?? 'svg',
                    'image_url' => ($asset->asset_type ?? 'svg') === 'png'
                        ? ($asset->resolvedImageDataUri() ?? $asset->image_url)
                        : null,
                    'path_count' => count($asset->paths ?? []),
                    'paths' => $asset->paths ?? [],
                    'view_box' => $asset->view_box,
                    'is_premium' => $asset->is_premium,
                    'svg' => $asset->toSvg('#111827', '#2563EB'),
                ];
            }),
        ]);
    }

    public function layouts(): JsonResponse
    {
        $data = Cache::remember('catalog.layouts', 3600, fn () => collect(Layouts::ALL)->map(
            fn (array $meta, string $slug) => ['slug' => $slug, 'label' => $meta['label'], 'orientation' => $meta['orientation']]
        )->values()->all());

        return response()->json(['data' => $data])
            ->header('Cache-Control', 'public, max-age=600');
    }

    public function plans(): JsonResponse
    {
        $data = Cache::remember('catalog.plans', 600, fn () => Plan::query()->active()->get()->map(fn (Plan $plan) => $plan->toPublicArray())->values()->all());

        return response()->json(['data' => $data])
            ->header('Cache-Control', 'public, max-age=300');
    }

    /**
     * Curated examples for the marketing homepage.
     */
    public function showcase(): JsonResponse
    {
        $data = Cache::remember('catalog.showcase', 600, fn () => LogoTemplate::query()
            ->with('industry:id,name')
            ->where('is_active', true)
            ->orderByDesc('popularity')
            ->take(8)
            ->get()
            ->map(fn (LogoTemplate $template) => [
                'id' => $template->id,
                'slug' => $template->slug,
                'name' => $template->name,
                'token' => $template->template_token,
                'industry' => $template->industry?->name,
                'layout' => $template->layout,
                'svg' => $template->svg,
                'design' => $template->design_json,
            ])->values()->all());

        return response()->json(['data' => $data])
            ->header('Cache-Control', 'public, max-age=300');
    }

    public function contactInfo(): JsonResponse
    {
        $saved = SiteSetting::get('general', []);
        $data = [
            'site_name' => $saved['site_name'] ?? 'Logo Design Nepal',
            'phone' => $saved['contact_phone'] ?? '+977 9800000000',
            'whatsapp' => $saved['contact_whatsapp'] ?? '9779800000000',
            'email' => $saved['contact_email'] ?? 'info@logodesignnepal.com',
        ];

        return response()->json(['data' => $data])
            ->header('Cache-Control', 'public, max-age=60');
    }
}
