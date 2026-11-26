<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ColorPalette;
use App\Models\Font;
use App\Models\Industry;
use App\Models\LogoStyle;
use App\Models\LogoTemplate;
use App\Services\Admin\AdminActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCatalogController extends Controller
{
    public function __construct(private readonly AdminActivityLogger $activity)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'industries' => Industry::query()->withCount('templates')->orderBy('sort_order')->get(),
                'categories' => Category::query()->withCount('templates')->orderBy('sort_order')->get(),
                'styles' => LogoStyle::query()->withCount('templates')->orderBy('sort_order')->get(),
                'fonts' => Font::query()->orderBy('sort_order')->get(),
                'palettes' => ColorPalette::query()->orderBy('sort_order')->get(),
            ],
        ]);
    }

    public function store(Request $request, string $resource): JsonResponse
    {
        $model = $this->model($resource);
        $record = $model::create($this->validated($request, $resource));
        $this->activity->log('catalog.created', $record, "Created {$resource} record {$record->name}.");

        return response()->json(['data' => $record], 201);
    }

    public function update(Request $request, string $resource, int $id): JsonResponse
    {
        $model = $this->model($resource);
        $record = $model::query()->findOrFail($id);
        $record->update($this->validated($request, $resource, $record));
        $this->activity->log('catalog.updated', $record, "Updated {$resource} record {$record->name}.");

        return response()->json(['data' => $record->fresh()]);
    }

    public function destroy(Request $request, string $resource, int $id): JsonResponse
    {
        $model = $this->model($resource);
        $record = $model::query()->findOrFail($id);

        if (! $request->boolean('force') && method_exists($record, 'templates') && $record->templates()->exists()) {
            return response()->json(['message' => 'This record is used by templates. Pass force to remove it and detach those templates.'], 409);
        }

        $name = $record->name;
        $record->delete();
        $this->activity->log('catalog.deleted', null, "Deleted {$resource} record {$name}.");

        return response()->json(['message' => 'Record deleted.']);
    }

    public function logoTemplates(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'industry' => ['nullable', 'string', 'max:80'],
            'style' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'in:published,draft,archived'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $templates = LogoTemplate::query()
            ->with(['industry:id,name,slug', 'category:id,name,slug', 'style:id,name,slug', 'font:id,name,family', 'symbol:id,name,slug,image_url,asset_type'])
            ->when($filters['q'] ?? null, function ($query, $q) {
                $query->where(fn ($inner) => $inner->where('name', 'like', "%{$q}%")->orWhere('slug', 'like', "%{$q}%"));
            })
            ->when($filters['industry'] ?? null, fn ($query, $industry) => $query->whereHas('industry', fn ($item) => $item->where('slug', $industry)))
            ->when($filters['style'] ?? null, fn ($query, $style) => $query->whereHas('style', fn ($item) => $item->where('slug', $style)))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 24));

        return response()->json([
            'data' => $templates->items(),
            'meta' => ['current_page' => $templates->currentPage(), 'last_page' => $templates->lastPage(), 'total' => $templates->total()],
        ]);
    }

    public function logoTemplate(LogoTemplate $template): JsonResponse
    {
        return response()->json(['data' => $template->load(['industry', 'category', 'style', 'font', 'symbol'])]);
    }

    public function updateLogoTemplate(Request $request, LogoTemplate $template): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'industry_id' => ['nullable', 'integer', 'exists:industries,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'style_id' => ['nullable', 'integer', 'exists:logo_styles,id'],
            'default_font_id' => ['nullable', 'integer', 'exists:fonts,id'],
            'symbol_asset_id' => ['nullable', 'integer', 'exists:logo_assets,id'],
            'layout' => ['sometimes', 'string', 'max:40'],
            'orientation' => ['sometimes', 'string', 'max:40'],
            'keywords' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
            'is_premium' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'in:published,draft,archived'],
        ]);

        $template->update($data);
        $this->activity->log('template.updated', $template, "Updated logo template {$template->name}.", $data);

        return response()->json(['data' => $template->fresh()->load(['industry', 'category', 'style', 'font', 'symbol'])]);
    }

    public function duplicateLogoTemplate(LogoTemplate $template): JsonResponse
    {
        $copy = $template->replicate(['template_token', 'slug']);
        $copy->name = $template->name.' copy';
        $copy->slug = Str::slug($copy->name).'-'.Str::lower(Str::random(5));
        $copy->status = 'draft';
        $copy->is_active = false;
        $copy->save();
        $this->activity->log('template.duplicated', $copy, "Duplicated logo template {$template->name}.");

        return response()->json(['data' => $copy], 201);
    }

    public function toggleLogoTemplate(LogoTemplate $template): JsonResponse
    {
        $template->update([
            'is_active' => ! $template->is_active,
            'status' => $template->is_active ? 'unpublished' : 'published',
        ]);
        $this->activity->log('template.toggled', $template, "Toggled logo template {$template->name}.");

        return response()->json(['data' => $template->fresh()]);
    }

    private function model(string $resource): string
    {
        return match ($resource) {
            'industries' => Industry::class,
            'categories' => Category::class,
            'styles' => LogoStyle::class,
            'fonts' => Font::class,
            'palettes' => ColorPalette::class,
            default => abort(404, 'Unknown catalog resource.'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, string $resource, ?Model $record = null): array
    {
        $required = $record ? 'sometimes' : 'required';

        return match ($resource) {
            'industries' => $request->validate([
                'name' => [$required, 'string', 'max:120'],
                'slug' => ['nullable', 'string', 'max:120', 'alpha_dash', 'unique:industries,slug'.($record ? ','.$record->id : '')],
                'tagline' => ['nullable', 'string', 'max:200'],
                'description' => ['nullable', 'string', 'max:5000'],
                'accent_color' => ['sometimes', 'string', 'max:16'],
                'keywords' => ['nullable', 'array'],
                'symbol_hints' => ['nullable', 'array'],
                'meta_title' => ['nullable', 'string', 'max:160'],
                'meta_description' => ['nullable', 'string', 'max:512'],
                'is_active' => ['sometimes', 'boolean'],
                'sort_order' => ['sometimes', 'integer', 'min:0'],
            ]),
            'categories', 'styles' => $request->validate([
                'name' => [$required, 'string', 'max:120'],
                'slug' => ['nullable', 'string', 'max:120', 'alpha_dash'],
                'description' => ['nullable', 'string', 'max:5000'],
                'is_active' => ['sometimes', 'boolean'],
                'sort_order' => ['sometimes', 'integer', 'min:0'],
            ]),
            'fonts' => $request->validate([
                'name' => [$required, 'string', 'max:120'],
                'slug' => ['nullable', 'string', 'max:120', 'alpha_dash'],
                'family' => [$required, 'string', 'max:120'],
                'google_family' => ['nullable', 'string', 'max:120'],
                'category' => ['sometimes', 'in:sans,serif,display,script,mono'],
                'weights' => ['nullable', 'array'],
                'has_italic' => ['sometimes', 'boolean'],
                'preview_text' => ['nullable', 'string', 'max:200'],
                'is_premium' => ['sometimes', 'boolean'],
                'is_active' => ['sometimes', 'boolean'],
                'sort_order' => ['sometimes', 'integer', 'min:0'],
            ]),
            'palettes' => $request->validate([
                'name' => [$required, 'string', 'max:120'],
                'slug' => ['nullable', 'string', 'max:120', 'alpha_dash'],
                'colors' => [$required, 'array', 'min:2'],
                'colors.*' => ['string', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'mood' => ['nullable', 'string', 'max:64'],
                'is_dark' => ['sometimes', 'boolean'],
                'is_active' => ['sometimes', 'boolean'],
                'sort_order' => ['sometimes', 'integer', 'min:0'],
            ]),
            default => abort(404, 'Unknown catalog resource.'),
        };
    }
}
