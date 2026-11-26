<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\TemplateCategory;
use App\Models\TemplateStyle;
use App\Models\TemplateTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TemplateTaxonomyAdminController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'categories' => TemplateCategory::query()->with('children')->orderBy('sort_order')->get(),
                'styles' => TemplateStyle::query()->orderBy('sort_order')->get(),
                'tags' => TemplateTag::query()->orderBy('name')->get(),
            ],
        ]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['nullable', 'string', 'max:80', 'alpha_dash', 'unique:template_categories,slug'],
            'parent_id' => ['nullable', 'integer', 'exists:template_categories,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:64'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $data['slug'] ??= Str::slug($data['name']);
        $category = TemplateCategory::create($data);

        return response()->json(['data' => $category], 201);
    }

    public function updateCategory(Request $request, TemplateCategory $category): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:80'],
            'slug' => ['nullable', 'string', 'max:80', 'alpha_dash', 'unique:template_categories,slug,'.$category->id],
            'parent_id' => ['nullable', 'integer', 'exists:template_categories,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:64'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $category->update($data);

        return response()->json(['data' => $category->fresh()]);
    }

    public function destroyCategory(TemplateCategory $category): JsonResponse
    {
        $category->delete();

        return response()->json(['message' => 'Category deleted.']);
    }

    public function storeStyle(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'slug' => ['nullable', 'string', 'max:60', 'alpha_dash', 'unique:template_styles,slug'],
            'description' => ['nullable', 'string', 'max:300'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $data['slug'] ??= Str::slug($data['name']);
        $style = TemplateStyle::create($data);

        return response()->json(['data' => $style], 201);
    }

    public function updateStyle(Request $request, TemplateStyle $style): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:60'],
            'slug' => ['nullable', 'string', 'max:60', 'alpha_dash', 'unique:template_styles,slug,'.$style->id],
            'description' => ['nullable', 'string', 'max:300'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $style->update($data);

        return response()->json(['data' => $style->fresh()]);
    }

    public function destroyStyle(TemplateStyle $style): JsonResponse
    {
        $style->delete();

        return response()->json(['message' => 'Style deleted.']);
    }

    public function storeTag(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'slug' => ['nullable', 'string', 'max:60', 'alpha_dash', 'unique:template_tags,slug'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['slug'] ??= Str::slug($data['name']);
        $tag = TemplateTag::create($data);

        return response()->json(['data' => $tag], 201);
    }

    public function updateTag(Request $request, TemplateTag $tag): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:60'],
            'slug' => ['nullable', 'string', 'max:60', 'alpha_dash', 'unique:template_tags,slug,'.$tag->id],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $tag->update($data);

        return response()->json(['data' => $tag->fresh()]);
    }

    public function destroyTag(TemplateTag $tag): JsonResponse
    {
        $tag->delete();

        return response()->json(['message' => 'Tag deleted.']);
    }
}
