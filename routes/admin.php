<?php

use App\Http\Controllers\Api\V1\Admin\AdminActivityController;
use App\Http\Controllers\Api\V1\Admin\AdminAiController;
use App\Http\Controllers\Api\V1\Admin\AdminCatalogController;
use App\Http\Controllers\Api\V1\Admin\AdminCollectionController;
use App\Http\Controllers\Api\V1\Admin\AdminContentController;
use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\AdminDesignController;
use App\Http\Controllers\Api\V1\Admin\AdminRoleController;
use App\Http\Controllers\Api\V1\Admin\AdminSettingsController;
use App\Http\Controllers\Api\V1\Admin\AdminSupportController;
use App\Http\Controllers\Api\V1\Admin\AdminSystemController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('dashboard', [AdminDashboardController::class, 'overview'])->middleware('permission:dashboard.view');
    Route::get('analytics', [AdminDashboardController::class, 'analytics'])->middleware('permission:analytics.view');
    Route::get('activity', [AdminActivityController::class, 'index'])->middleware('permission:activity.view');

    Route::get('roles', [AdminRoleController::class, 'index'])->middleware('permission:roles.manage');
    Route::post('roles', [AdminRoleController::class, 'store'])->middleware('permission:roles.manage');
    Route::put('roles/{role}', [AdminRoleController::class, 'update'])->middleware('permission:roles.manage');
    Route::delete('roles/{role}', [AdminRoleController::class, 'destroy'])->middleware('permission:roles.manage');

    Route::get('users/{user}', [AdminUserController::class, 'show'])->middleware('permission:users.view');
    Route::patch('users/{user}', [AdminUserController::class, 'update'])->middleware('permission:users.manage');
    Route::post('users/{user}/suspend', [AdminUserController::class, 'suspend'])->middleware('permission:users.manage');
    Route::post('users/{user}/restore', [AdminUserController::class, 'restore'])->middleware('permission:users.manage');
    Route::get('favorites', [AdminUserController::class, 'favorites'])->middleware('permission:users.view');
    Route::delete('favorites/{favorite}', [AdminUserController::class, 'destroyFavorite'])->middleware('permission:users.manage');

    Route::get('designs/{design}', [AdminDesignController::class, 'show'])->middleware('permission:designs.view');
    Route::patch('designs/{design}/moderation', [AdminDesignController::class, 'moderate'])->middleware('permission:designs.moderate');
    Route::post('designs/{design}/archive', [AdminDesignController::class, 'archive'])->middleware('permission:designs.moderate');
    Route::post('designs/{design}/restore', [AdminDesignController::class, 'restore'])->middleware('permission:designs.moderate');

    Route::prefix('ai')->group(function () {
        Route::get('providers', [AdminAiController::class, 'providers'])->middleware('permission:ai.view');
        Route::post('providers', [AdminAiController::class, 'storeProvider'])->middleware('permission:ai.providers');
        Route::put('providers/{provider}', [AdminAiController::class, 'updateProvider'])->middleware('permission:ai.providers');
        Route::delete('providers/{provider}', [AdminAiController::class, 'destroyProvider'])->middleware('permission:ai.providers');
        Route::post('providers/{provider}/test', [AdminAiController::class, 'testProvider'])->middleware('permission:ai.providers');

        Route::get('prompts', [AdminAiController::class, 'prompts'])->middleware('permission:ai.view');
        Route::post('prompts', [AdminAiController::class, 'storePrompt'])->middleware('permission:ai.prompts');
        Route::put('prompts/{prompt}', [AdminAiController::class, 'updatePrompt'])->middleware('permission:ai.prompts');
        Route::delete('prompts/{prompt}', [AdminAiController::class, 'destroyPrompt'])->middleware('permission:ai.prompts');
        Route::post('prompts/{prompt}/test', [AdminAiController::class, 'testPrompt'])->middleware('permission:ai.prompts');

        Route::get('generations', [AdminAiController::class, 'generations'])->middleware('permission:ai.view');
        Route::get('generations/{generation}', [AdminAiController::class, 'generation'])->middleware('permission:ai.view');
        Route::post('generations/{generation}/review', [AdminAiController::class, 'reviewGeneration'])->middleware('permission:ai.generations');

        Route::get('jobs', [AdminAiController::class, 'jobs'])->middleware('permission:ai.view');
        Route::post('jobs/{id}/retry', [AdminAiController::class, 'retryJob'])->middleware('permission:ai.jobs');
        Route::post('jobs/{id}/forget', [AdminAiController::class, 'forgetJob'])->middleware('permission:ai.jobs');
        Route::post('jobs/{id}/cancel', [AdminAiController::class, 'cancelJob'])->middleware('permission:ai.jobs');

        Route::get('costs', [AdminAiController::class, 'costs'])->middleware('permission:ai.view');
        Route::post('prices', [AdminAiController::class, 'storePrice'])->middleware('permission:ai.costs');
        Route::put('prices/{price}', [AdminAiController::class, 'updatePrice'])->middleware('permission:ai.costs');
        Route::delete('prices/{price}', [AdminAiController::class, 'destroyPrice'])->middleware('permission:ai.costs');
    });

    Route::get('catalog', [AdminCatalogController::class, 'index'])->middleware('permission:assets.view');
    Route::post('catalog/{resource}', [AdminCatalogController::class, 'store'])->middleware('permission:catalog.manage');
    Route::put('catalog/{resource}/{id}', [AdminCatalogController::class, 'update'])->middleware('permission:catalog.manage');
    Route::delete('catalog/{resource}/{id}', [AdminCatalogController::class, 'destroy'])->middleware('permission:catalog.manage');
    Route::get('logo-templates', [AdminCatalogController::class, 'logoTemplates'])->middleware('permission:templates.view');
    Route::get('logo-templates/{template}', [AdminCatalogController::class, 'logoTemplate'])->middleware('permission:templates.view');
    Route::put('logo-templates/{template}', [AdminCatalogController::class, 'updateLogoTemplate'])->middleware('permission:templates.manage');
    Route::post('logo-templates/{template}/duplicate', [AdminCatalogController::class, 'duplicateLogoTemplate'])->middleware('permission:templates.manage');
    Route::post('logo-templates/{template}/toggle', [AdminCatalogController::class, 'toggleLogoTemplate'])->middleware('permission:templates.manage');

    Route::get('collections', [AdminCollectionController::class, 'index'])->middleware('permission:templates.view');
    Route::post('collections', [AdminCollectionController::class, 'store'])->middleware('permission:collections.manage');
    Route::put('collections/{collection}', [AdminCollectionController::class, 'update'])->middleware('permission:collections.manage');
    Route::delete('collections/{collection}', [AdminCollectionController::class, 'destroy'])->middleware('permission:collections.manage');
    Route::post('collections/{collection}/templates', [AdminCollectionController::class, 'syncTemplates'])->middleware('permission:collections.manage');

    Route::get('content', [AdminContentController::class, 'index'])->middleware('permission:content.view');
    Route::post('content/pages', [AdminContentController::class, 'storePage'])->middleware('permission:content.manage');
    Route::put('content/pages/{page}', [AdminContentController::class, 'updatePage'])->middleware('permission:content.manage');
    Route::delete('content/pages/{page}', [AdminContentController::class, 'destroyPage'])->middleware('permission:content.manage');
    Route::post('content/faqs', [AdminContentController::class, 'storeFaq'])->middleware('permission:content.manage');
    Route::put('content/faqs/{faq}', [AdminContentController::class, 'updateFaq'])->middleware('permission:content.manage');
    Route::delete('content/faqs/{faq}', [AdminContentController::class, 'destroyFaq'])->middleware('permission:content.manage');
    Route::post('content/announcements', [AdminContentController::class, 'storeAnnouncement'])->middleware('permission:content.manage');
    Route::put('content/announcements/{announcement}', [AdminContentController::class, 'updateAnnouncement'])->middleware('permission:content.manage');
    Route::delete('content/announcements/{announcement}', [AdminContentController::class, 'destroyAnnouncement'])->middleware('permission:content.manage');
    Route::put('content/seo', [AdminContentController::class, 'updateSeo'])->middleware('permission:content.manage');

    Route::get('support', [AdminSupportController::class, 'index'])->middleware('permission:support.view');
    Route::post('support/tickets', [AdminSupportController::class, 'storeTicket'])->middleware('permission:support.manage');
    Route::get('support/tickets/{ticket}', [AdminSupportController::class, 'showTicket'])->middleware('permission:support.view');
    Route::patch('support/tickets/{ticket}', [AdminSupportController::class, 'updateTicket'])->middleware('permission:support.manage');
    Route::post('support/tickets/{ticket}/replies', [AdminSupportController::class, 'reply'])->middleware('permission:support.manage');
    Route::patch('support/reports/{report}', [AdminSupportController::class, 'updateReport'])->middleware('permission:support.manage');

    Route::get('settings', [AdminSettingsController::class, 'index'])->middleware('permission:settings.manage');
    Route::put('settings', [AdminSettingsController::class, 'update'])->middleware('permission:settings.manage');
    Route::get('settings/features', [AdminSettingsController::class, 'features'])->middleware('permission:features.manage');
    Route::put('settings/features', [AdminSettingsController::class, 'updateFeatures'])->middleware('permission:features.manage');

    Route::get('system/health', [AdminSystemController::class, 'health'])->middleware('permission:system.view');
    Route::get('system/queue', [AdminSystemController::class, 'queue'])->middleware('permission:system.view');
    Route::post('system/queue/{id}/retry', [AdminSystemController::class, 'retry'])->middleware('permission:system.view');
    Route::post('system/queue/{id}/forget', [AdminSystemController::class, 'forget'])->middleware('permission:system.view');
    Route::post('system/queue/{id}/cancel', [AdminSystemController::class, 'cancel'])->middleware('permission:system.view');
});
