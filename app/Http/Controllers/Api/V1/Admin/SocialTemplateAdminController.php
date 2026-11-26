<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSocialTemplateRequest;
use App\Http\Requests\Api\UpdateSocialTemplateRequest;
use App\Http\Resources\SocialTemplateResource;
use App\Http\Resources\SocialTemplateSummaryResource;
use App\Http\Resources\SocialTemplateVariationResource;
use App\Models\SocialTemplate;
use App\Models\SocialTemplateVariation;
use App\Services\Social\SocialTemplateAiService;
use App\Services\Social\SocialTemplateAnalyticsService;
use App\Services\Social\SocialTemplateService;
use App\Services\Social\SocialTemplateVariationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocialTemplateAdminController extends Controller
{
    public function __construct(
        private readonly SocialTemplateService $templates,
        private readonly SocialTemplateVariationService $variations,
        private readonly SocialTemplateAiService $ai,
        private readonly SocialTemplateAnalyticsService $analytics,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:draft,published,archived'],
            'platform' => ['nullable', 'string', 'max:80'],
            'premium' => ['nullable', 'in:all,free,premium'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:60'],
        ]);

        $query = SocialTemplate::query()
            ->with(['platform', 'designType', 'industry', 'category'])
            ->withCount(['versions', 'variations', 'favorites']);

        if (! empty($filters['q'])) {
            $search = trim((string) $filters['q']);
            $query->where(function ($scope) use ($search) {
                $scope->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $query->forPlatform($filters['platform'] ?? null);

        if (($filters['premium'] ?? 'all') === 'free') {
            $query->where('is_premium', false);
        } elseif (($filters['premium'] ?? 'all') === 'premium') {
            $query->where('is_premium', true);
        }

        $templates = $query->latest('updated_at')->paginate((int) ($filters['per_page'] ?? 24));

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

    public function show(SocialTemplate $template): JsonResponse
    {
        $template->load(['platform', 'designType', 'industry', 'category'])
            ->loadCount(['versions', 'variations', 'favorites']);

        return response()->json(['data' => (new SocialTemplateResource($template))->resolve()]);
    }

    public function store(StoreSocialTemplateRequest $request): JsonResponse
    {
        $template = $this->templates->create($request->validated(), $request->user());

        return response()
            ->json(['data' => (new SocialTemplateResource($template))->resolve()], 201);
    }

    public function update(UpdateSocialTemplateRequest $request, SocialTemplate $template): JsonResponse
    {
        $updated = $this->templates->update($template, $request->validated(), $request->user());

        return response()->json(['data' => (new SocialTemplateResource($updated))->resolve()]);
    }

    public function destroy(SocialTemplate $template): JsonResponse
    {
        $template->delete();

        return response()->json(['message' => 'Template deleted.']);
    }

    public function publish(SocialTemplate $template): JsonResponse
    {
        $published = $this->templates->publish($template);

        return response()->json(['data' => (new SocialTemplateResource($published))->resolve()]);
    }

    public function unpublish(SocialTemplate $template): JsonResponse
    {
        return response()->json(['data' => (new SocialTemplateResource($this->templates->unpublish($template)))->resolve()]);
    }

    public function archive(SocialTemplate $template): JsonResponse
    {
        return response()->json(['data' => (new SocialTemplateResource($this->templates->archive($template)))->resolve()]);
    }

    public function duplicate(Request $request, SocialTemplate $template): JsonResponse
    {
        $copy = $this->templates->duplicate($template, $request->user());

        return response()->json(['data' => (new SocialTemplateResource($copy))->resolve()], 201);
    }

    public function bulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'exists:social_templates,id'],
            'action' => ['required', 'in:publish,unpublish,archive,delete'],
        ]);

        return response()->json(['data' => $this->templates->bulk($validated['ids'], $validated['action'])]);
    }

    public function variations(SocialTemplate $template): JsonResponse
    {
        $variations = $template->variations()->get();

        return response()->json(['data' => SocialTemplateVariationResource::collection($variations)->resolve()]);
    }

    public function generateVariations(Request $request, SocialTemplate $template): JsonResponse
    {
        $validated = $request->validate(['count' => ['nullable', 'integer', 'min:1', 'max:24']]);
        $created = $this->variations->generate($template, (int) ($validated['count'] ?? 6), $request->user());

        return response()->json([
            'data' => SocialTemplateVariationResource::collection($created)->resolve(),
            'message' => $created->count().' variations generated.',
        ], 201);
    }

    public function approveVariation(SocialTemplateVariation $variation): JsonResponse
    {
        return response()->json(['data' => (new SocialTemplateVariationResource($this->variations->approve($variation)))->resolve()]);
    }

    public function publishVariation(Request $request, SocialTemplateVariation $variation): JsonResponse
    {
        $template = $this->variations->publish($variation, $this->templates, $request->user());

        return response()->json(['data' => (new SocialTemplateResource($template))->resolve()], 201);
    }

    public function destroyVariation(SocialTemplateVariation $variation): JsonResponse
    {
        $variation->delete();

        return response()->json(['message' => 'Variation deleted.']);
    }

    public function aiSuggest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'industry' => ['nullable', 'string', 'max:80'],
            'platform' => ['nullable', 'string', 'max:80'],
            'design_type' => ['nullable', 'string', 'max:80'],
            'category' => ['nullable', 'string', 'max:80'],
            'brand' => ['nullable', 'string', 'max:80'],
            'style' => ['nullable', 'string', 'max:40'],
        ]);

        return response()->json(['data' => $this->ai->suggest($validated)]);
    }

    public function generateAsset(Request $request): JsonResponse
    {
        $validated = $request->validate(['prompt' => ['required', 'string', 'min:4', 'max:600']]);

        return response()->json(['data' => ['urls' => $this->ai->generateAsset($validated['prompt'])]]);
    }

    public function analytics(): JsonResponse
    {
        return response()->json(['data' => $this->analytics->summary()]);
    }

    public function export(SocialTemplate $template): JsonResponse
    {
        return response()->json($this->templates->export($template));
    }

    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:140'],
            'design_type' => ['required', 'string', 'exists:social_design_types,slug'],
            'design_json' => ['required', 'array'],
        ]);

        $template = $this->templates->import(array_merge($request->all(), $validated), $request->user());

        return response()->json(['data' => (new SocialTemplateResource($template))->resolve()], 201);
    }
}
