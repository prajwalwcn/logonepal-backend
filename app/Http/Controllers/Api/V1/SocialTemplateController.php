<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\GenerateTemplateCollectionRequest;
use App\Http\Requests\Api\ResizeSocialTemplateRequest;
use App\Http\Resources\SocialDesignTypeResource;
use App\Http\Resources\SocialPlatformResource;
use App\Http\Resources\SocialTemplateCollectionResource;
use App\Http\Resources\SocialTemplateResource;
use App\Http\Resources\SocialTemplateSummaryResource;
use App\Models\SocialDesignType;
use App\Models\SocialPlatform;
use App\Models\SocialTemplate;
use App\Models\SocialTemplateCollection;
use App\Models\SocialTemplateFavorite;
use App\Models\TemplateCategory;
use App\Models\TemplateStyle;
use App\Models\TemplateTag;
use App\Models\UserDesign;
use App\Services\Social\SocialTemplateAnalyticsService;
use App\Services\Social\SocialTemplateCollectionService;
use App\Services\Social\SocialTemplateComposer;
use App\Services\Social\SocialTemplateQualityService;
use App\Services\Social\SocialTemplateResizeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class SocialTemplateController extends Controller
{
    public function __construct(
        private readonly SocialTemplateAnalyticsService $analytics,
        private readonly SocialTemplateResizeService $resizer,
        private readonly SocialTemplateComposer $composer,
        private readonly SocialTemplateCollectionService $collections,
        private readonly SocialTemplateQualityService $quality,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'platform' => ['nullable', 'string', 'max:80'],
            'design_type' => ['nullable', 'string', 'max:80'],
            'industry' => ['nullable', 'string', 'max:80'],
            'category' => ['nullable', 'string', 'max:80'],
            'style' => ['nullable', 'string', 'max:40'],
            'q' => ['nullable', 'string', 'max:120'],
            'trending' => ['nullable', 'boolean'],
            'featured' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:newest,popular,trending,featured,quality'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:48'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = SocialTemplate::query()
            ->published()
            ->with(['platform', 'designType', 'industry', 'category']);

        if ($request->user() !== null) {
            $query->withExists([
                'favorites as is_favorited' => fn ($favorite) => $favorite->where('user_id', $request->user()->id),
            ]);
        }

        $query
            ->forPlatform($filters['platform'] ?? null)
            ->forDesignType($filters['design_type'] ?? null)
            ->forIndustry($filters['industry'] ?? null)
            ->forCategory($filters['category'] ?? null)
            ->forStyle($filters['style'] ?? null);

        if (! empty($filters['trending'])) {
            $query->where('is_trending', true);
        }

        if (! empty($filters['featured'])) {
            $query->where('is_featured', true);
        }

        if (! empty($filters['q'])) {
            $search = trim((string) $filters['q']);
            $query->where(function ($scope) use ($search) {
                $scope->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereJsonContains('keywords', $search)
                    ->orWhereJsonContains('tags', $search);
            });

            $this->analytics->recordSearch($search, $request->user());
        }

        match ($filters['sort'] ?? 'newest') {
            'popular' => $query->orderByDesc('usage_count')->orderByDesc('view_count'),
            'trending' => $query->orderByDesc('is_trending')->orderByDesc('usage_count'),
            'featured' => $query->orderByDesc('is_featured')->orderByDesc('published_at'),
            'quality' => $query->orderByDesc('quality_score')->orderByDesc('published_at'),
            default => $query->orderByDesc('published_at'),
        };

        $perPage = (int) ($filters['per_page'] ?? 24);
        $templates = $query->paginate($perPage);

        return response()->json([
            'data' => SocialTemplateSummaryResource::collection($templates->items())->resolve($request),
            'meta' => [
                'current_page' => $templates->currentPage(),
                'last_page' => $templates->lastPage(),
                'per_page' => $templates->perPage(),
                'total' => $templates->total(),
            ],
        ]);
    }

    public function show(Request $request, SocialTemplate $template): JsonResponse
    {
        abort_unless($template->status === SocialTemplate::STATUS_PUBLISHED, 404);

        $template->load(['platform', 'designType', 'industry', 'category'])
            ->loadCount(['versions', 'variations', 'favorites']);

        if ($request->user() !== null) {
            $template->setAttribute(
                'is_favorited',
                $template->favorites()->where('user_id', $request->user()->id)->exists()
            );
        }

        $this->analytics->record($template, 'view', $request->user());

        return response()->json(['data' => (new SocialTemplateResource($template))->resolve($request)]);
    }

    public function preview(Request $request, SocialTemplate $template): Response
    {
        abort_unless($template->status === SocialTemplate::STATUS_PUBLISHED, 404);

        $svg = $template->svg ?: $this->composer->render($template->design_json ?? []);
        $etag = '"'.md5($template->slug.'|'.$template->version.'|'.$svg).'"';

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304, ['ETag' => $etag]);
        }

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400, immutable',
            'ETag' => $etag,
        ]);
    }

    public function layers(SocialTemplate $template): JsonResponse
    {
        abort_unless($template->status === SocialTemplate::STATUS_PUBLISHED, 404);
        $template->loadMissing('designType');

        return response()->json([
            'data' => [
                'uuid' => $template->uuid,
                'design' => $template->design_json,
                'layer_config' => $template->layer_config,
                'safe_area' => $template->safeArea(),
                'canvas' => $template->design_json['canvas'] ?? [
                    'width' => $template->width,
                    'height' => $template->height,
                ],
                'quality' => $template->quality_report,
            ],
        ]);
    }

    public function similar(SocialTemplate $template): JsonResponse
    {
        abort_unless($template->status === SocialTemplate::STATUS_PUBLISHED, 404);

        $similar = SocialTemplate::query()
            ->published()
            ->with(['platform', 'designType', 'industry', 'category'])
            ->whereKeyNot($template->id)
            ->where(function ($query) use ($template) {
                $query->where('platform_id', $template->platform_id)
                    ->orWhere('industry_id', $template->industry_id)
                    ->orWhere('design_type_id', $template->design_type_id);
            })
            ->orderByDesc('usage_count')
            ->limit(8)
            ->get();

        return response()->json([
            'data' => SocialTemplateSummaryResource::collection($similar)->resolve(),
        ]);
    }

    public function favorite(Request $request, SocialTemplate $template): JsonResponse
    {
        abort_unless($template->status === SocialTemplate::STATUS_PUBLISHED, 404);

        $existing = SocialTemplateFavorite::query()
            ->where('user_id', $request->user()->id)
            ->where('template_id', $template->id)
            ->first();

        if ($existing !== null) {
            $existing->delete();
            $template->decrement('favorite_count');

            return response()->json(['data' => ['favorited' => false, 'favorite_count' => max(0, $template->fresh()->favorite_count)]]);
        }

        SocialTemplateFavorite::create([
            'user_id' => $request->user()->id,
            'template_id' => $template->id,
        ]);

        $this->analytics->record($template, 'favorite', $request->user());

        return response()->json(['data' => ['favorited' => true, 'favorite_count' => $template->fresh()->favorite_count]]);
    }

    public function use(Request $request, SocialTemplate $template): JsonResponse
    {
        abort_unless($template->status === SocialTemplate::STATUS_PUBLISHED, 404);

        $brand = trim((string) $request->input('brand', ''));
        $design = $template->design_json ?? [];

        if ($brand !== '') {
            foreach (($design['objects'] ?? []) as $index => $object) {
                if (($object['role'] ?? null) === 'primary-text' && ($object['type'] ?? null) === 'i-text') {
                    $design['objects'][$index]['text'] = mb_substr($brand, 0, 80);
                }
            }
        }

        $svg = $this->composer->render($design);

        $saved = UserDesign::create([
            'user_id' => $request->user()->id,
            'guest_token' => null,
            'generation_id' => null,
            'generated_logo_id' => null,
            'name' => ($brand !== '' ? $brand : $template->name).' — '.$template->name,
            'business_name' => mb_substr($brand !== '' ? $brand : $template->name, 0, 120),
            'tagline' => null,
            'format' => $template->designType?->slug,
            'design_json' => $design,
            'svg' => $svg,
            'is_draft' => false,
            'last_edited_at' => now(),
        ]);

        $this->analytics->record($template, 'use', $request->user(), ['design_uuid' => $saved->uuid]);

        return response()->json([
            'data' => [
                'uuid' => $saved->uuid,
                'name' => $saved->name,
                'edit_href' => '/editor?design='.$saved->uuid,
                'svg' => $svg,
            ],
        ], 201);
    }

    public function resize(ResizeSocialTemplateRequest $request): JsonResponse
    {
        $target = SocialDesignType::query()->with('platform')->where('slug', $request->string('target_design_type'))->firstOrFail();

        if ($request->filled('template_uuid')) {
            $template = SocialTemplate::query()->published()->where('uuid', $request->string('template_uuid'))->firstOrFail();
            $design = $template->design_json ?? [];
        } else {
            $design = (array) $request->input('design_json', []);
        }

        if ($request->filled('brand')) {
            foreach (($design['objects'] ?? []) as $index => $object) {
                if (($object['role'] ?? null) === 'primary-text' && ($object['type'] ?? null) === 'i-text') {
                    $design['objects'][$index]['text'] = mb_substr($request->string('brand')->toString(), 0, 80);
                }
            }
        }

        $resized = $this->resizer->resize($design, $target);

        return response()->json([
            'data' => [
                'design' => $resized,
                'svg' => $this->composer->render($resized),
                'quality' => $this->quality->inspect($resized, $target),
                'target' => (new SocialDesignTypeResource($target))->resolve($request),
            ],
        ]);
    }

    public function platforms(): JsonResponse
    {
        $platforms = SocialPlatform::query()
            ->active()
            ->with(['designTypes' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
            ->withCount(['templates' => fn ($query) => $query->published()])
            ->get();

        return response()->json(['data' => SocialPlatformResource::collection($platforms)->resolve()]);
    }

    public function designTypes(Request $request): JsonResponse
    {
        $platform = $request->string('platform')->toString();

        $types = SocialDesignType::query()
            ->active()
            ->with('platform')
            ->when($platform !== '', fn ($query) => $query->whereHas('platform', fn ($p) => $p->where('slug', $platform)))
            ->withCount('templates')
            ->get();

        return response()->json(['data' => SocialDesignTypeResource::collection($types)->resolve()]);
    }

    public function styles(): JsonResponse
    {
        return response()->json([
            'data' => TemplateStyle::query()->active()->get(['id', 'name', 'slug', 'description']),
        ]);
    }

    public function categories(): JsonResponse
    {
        return response()->json([
            'data' => TemplateCategory::query()->active()->withCount('templates')->get(['id', 'name', 'slug', 'description', 'icon']),
        ]);
    }

    public function tags(): JsonResponse
    {
        return response()->json([
            'data' => TemplateTag::query()->active()->get(['id', 'name', 'slug']),
        ]);
    }

    public function sitemap(): JsonResponse
    {
        return response()->json([
            'data' => SocialTemplate::query()
                ->published()
                ->orderByDesc('updated_at')
                ->get(['slug', 'updated_at'])
                ->map(fn (SocialTemplate $template) => [
                    'slug' => $template->slug,
                    'updated_at' => $template->updated_at?->toIso8601String(),
                ]),
        ]);
    }

    public function collections(): JsonResponse
    {
        $collections = SocialTemplateCollection::query()
            ->active()
            ->withCount('templates')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => SocialTemplateCollectionResource::collection($collections)->resolve()]);
    }

    public function collection(SocialTemplateCollection $collection): JsonResponse
    {
        abort_unless($collection->is_active, 404);

        $collection->load([
            'templates' => fn ($query) => $query->published()->with(['platform', 'designType', 'industry', 'category']),
        ])->loadCount('templates');

        return response()->json(['data' => (new SocialTemplateCollectionResource($collection))->resolve()]);
    }

    public function generateCollection(GenerateTemplateCollectionRequest $request): JsonResponse
    {
        $result = $this->collections->generateForUser($request->user(), $request->validated());

        return response()->json(['data' => $result], 201);
    }
}
