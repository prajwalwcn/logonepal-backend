<?php

use App\Http\Controllers\Api\V1\Admin\DesignEngineAdminController;
use App\Http\Controllers\Api\V1\Admin\SocialPlatformAdminController;
use App\Http\Controllers\Api\V1\Admin\SocialTemplateAdminController;
use App\Http\Controllers\Api\V1\Admin\TemplateTaxonomyAdminController;
use App\Http\Controllers\Api\V1\SocialTemplateController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('social-templates')->group(function () {
        Route::get('/', [SocialTemplateController::class, 'index'])->middleware('throttle:120,1');
        Route::get('platforms', [SocialTemplateController::class, 'platforms']);
        Route::get('design-types', [SocialTemplateController::class, 'designTypes']);
        Route::get('styles', [SocialTemplateController::class, 'styles']);
        Route::get('categories', [SocialTemplateController::class, 'categories']);
        Route::get('tags', [SocialTemplateController::class, 'tags']);
        Route::get('sitemap', [SocialTemplateController::class, 'sitemap']);
        Route::get('collections', [SocialTemplateController::class, 'collections']);
        Route::get('collections/{collection}', [SocialTemplateController::class, 'collection']);
        Route::post('resize', [SocialTemplateController::class, 'resize'])->middleware('throttle:30,1');

        Route::get('{template}/preview.svg', [SocialTemplateController::class, 'preview'])
            ->name('api.social-templates.preview');
        Route::get('{template}/layers', [SocialTemplateController::class, 'layers']);
        Route::get('{template}/similar', [SocialTemplateController::class, 'similar']);
        Route::get('{template}', [SocialTemplateController::class, 'show']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('social-templates/collections/generate', [SocialTemplateController::class, 'generateCollection'])
            ->middleware('throttle:12,1');
        Route::post('social-templates/{template}/favorite', [SocialTemplateController::class, 'favorite']);
        Route::post('social-templates/{template}/use', [SocialTemplateController::class, 'use'])
            ->middleware('throttle:30,1');
    });

    Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::get('social-templates/analytics', [SocialTemplateAdminController::class, 'analytics']);
        Route::get('social-templates', [SocialTemplateAdminController::class, 'index']);
        Route::post('social-templates', [SocialTemplateAdminController::class, 'store']);
        Route::post('social-templates/bulk', [SocialTemplateAdminController::class, 'bulk']);
        Route::post('social-templates/import', [SocialTemplateAdminController::class, 'import']);
        Route::post('social-templates/ai/suggest', [SocialTemplateAdminController::class, 'aiSuggest']);
        Route::post('social-templates/ai/asset', [SocialTemplateAdminController::class, 'generateAsset']);
        Route::get('social-templates/{template}/export', [SocialTemplateAdminController::class, 'export']);
        Route::get('social-templates/{template}/variations', [SocialTemplateAdminController::class, 'variations']);
        Route::post('social-templates/{template}/variations', [SocialTemplateAdminController::class, 'generateVariations']);
        Route::post('social-templates/{template}/publish', [SocialTemplateAdminController::class, 'publish']);
        Route::post('social-templates/{template}/unpublish', [SocialTemplateAdminController::class, 'unpublish']);
        Route::post('social-templates/{template}/archive', [SocialTemplateAdminController::class, 'archive']);
        Route::post('social-templates/{template}/duplicate', [SocialTemplateAdminController::class, 'duplicate']);
        Route::get('social-templates/{template}', [SocialTemplateAdminController::class, 'show']);
        Route::put('social-templates/{template}', [SocialTemplateAdminController::class, 'update']);
        Route::patch('social-templates/{template}', [SocialTemplateAdminController::class, 'update']);
        Route::delete('social-templates/{template}', [SocialTemplateAdminController::class, 'destroy']);

        Route::post('social-template-variations/{variation}/approve', [SocialTemplateAdminController::class, 'approveVariation']);
        Route::post('social-template-variations/{variation}/publish', [SocialTemplateAdminController::class, 'publishVariation']);
        Route::delete('social-template-variations/{variation}', [SocialTemplateAdminController::class, 'destroyVariation']);

        Route::get('social-platforms', [SocialPlatformAdminController::class, 'index']);
        Route::post('social-platforms', [SocialPlatformAdminController::class, 'store']);
        Route::put('social-platforms/{platform}', [SocialPlatformAdminController::class, 'update']);
        Route::delete('social-platforms/{platform}', [SocialPlatformAdminController::class, 'destroy']);
        Route::post('social-platforms/{platform}/design-types', [SocialPlatformAdminController::class, 'storeDesignType']);
        Route::put('social-design-types/{designType}', [SocialPlatformAdminController::class, 'updateDesignType']);
        Route::delete('social-design-types/{designType}', [SocialPlatformAdminController::class, 'destroyDesignType']);

        Route::get('template-taxonomy', [TemplateTaxonomyAdminController::class, 'index']);
        Route::post('template-taxonomy/categories', [TemplateTaxonomyAdminController::class, 'storeCategory']);
        Route::put('template-taxonomy/categories/{category}', [TemplateTaxonomyAdminController::class, 'updateCategory']);
        Route::delete('template-taxonomy/categories/{category}', [TemplateTaxonomyAdminController::class, 'destroyCategory']);
        Route::post('template-taxonomy/styles', [TemplateTaxonomyAdminController::class, 'storeStyle']);
        Route::put('template-taxonomy/styles/{style}', [TemplateTaxonomyAdminController::class, 'updateStyle']);
        Route::delete('template-taxonomy/styles/{style}', [TemplateTaxonomyAdminController::class, 'destroyStyle']);
        Route::post('template-taxonomy/tags', [TemplateTaxonomyAdminController::class, 'storeTag']);
        Route::put('template-taxonomy/tags/{tag}', [TemplateTaxonomyAdminController::class, 'updateTag']);
        Route::delete('template-taxonomy/tags/{tag}', [TemplateTaxonomyAdminController::class, 'destroyTag']);

        Route::prefix('design-engine')->group(function () {
            Route::get('/', [DesignEngineAdminController::class, 'index']);
            Route::post('presets/{type}', [DesignEngineAdminController::class, 'storePreset']);
            Route::put('presets/{type}/{id}', [DesignEngineAdminController::class, 'updatePreset']);
            Route::post('presets/{type}/{id}/duplicate', [DesignEngineAdminController::class, 'duplicatePreset']);
            Route::delete('presets/{type}/{id}', [DesignEngineAdminController::class, 'destroyPreset']);
            Route::post('templates/{template}/beautify-preview', [DesignEngineAdminController::class, 'beautifyPreview']);
            Route::post('templates/{template}/beautify-apply', [DesignEngineAdminController::class, 'beautifyApply']);
            Route::get('templates/{template}/versions', [DesignEngineAdminController::class, 'versions']);
            Route::post('templates/{template}/versions/{version}/restore', [DesignEngineAdminController::class, 'restoreVersion']);
        });
    });
});
