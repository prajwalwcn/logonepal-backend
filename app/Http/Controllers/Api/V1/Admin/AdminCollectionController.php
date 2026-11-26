<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocialTemplate;
use App\Models\SocialTemplateCollection;
use App\Services\Admin\AdminActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCollectionController extends Controller
{
    public function __construct(private readonly AdminActivityLogger $activity)
    {
    }

    public function index(): JsonResponse
    {
        $collections = SocialTemplateCollection::query()
            ->withCount('templates')
            ->with(['templates:id,name,slug,status'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $collections,
            'templates' => SocialTemplate::query()->published()->orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']).'-'.Str::lower(Str::random(4));
        $data['created_by'] = $request->user()->id;
        $collection = SocialTemplateCollection::create($data);
        $this->activity->log('collection.created', $collection, "Created collection {$collection->name}.");

        return response()->json(['data' => $collection], 201);
    }

    public function update(Request $request, SocialTemplateCollection $collection): JsonResponse
    {
        $collection->update($this->validated($request, $collection));
        $this->activity->log('collection.updated', $collection, "Updated collection {$collection->name}.");

        return response()->json(['data' => $collection->fresh()->loadCount('templates')]);
    }

    public function destroy(SocialTemplateCollection $collection): JsonResponse
    {
        $name = $collection->name;
        $collection->templates()->detach();
        $collection->delete();
        $this->activity->log('collection.deleted', null, "Deleted collection {$name}.");

        return response()->json(['message' => 'Collection deleted.']);
    }

    public function syncTemplates(Request $request, SocialTemplateCollection $collection): JsonResponse
    {
        $data = $request->validate([
            'template_ids' => ['required', 'array'],
            'template_ids.*' => ['integer', 'exists:social_templates,id'],
        ]);

        $sync = collect($data['template_ids'])->values()->mapWithKeys(fn ($id, $index) => [$id => ['sort_order' => $index]])->all();
        $collection->templates()->sync($sync);
        $this->activity->log('collection.templates.synced', $collection, "Updated templates in collection {$collection->name}.");

        return response()->json(['data' => $collection->fresh()->load('templates:id,name,slug,status')]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?SocialTemplateCollection $collection = null): array
    {
        return $request->validate([
            'name' => [$collection ? 'sometimes' : 'required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:160', 'alpha_dash', 'unique:social_template_collections,slug'.($collection ? ','.$collection->id : '')],
            'description' => ['nullable', 'string', 'max:2000'],
            'cover_path' => ['nullable', 'string', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
