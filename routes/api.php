<?php

use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DesignController;
use App\Http\Controllers\Api\V1\DesignFormatController;
use App\Http\Controllers\Api\V1\DownloadController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\GenerationController;
use App\Http\Controllers\Api\V1\LogoController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\RenderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Public catalogue and generation
    |--------------------------------------------------------------------------
    | Guests can generate, browse, edit and preview without an account.
    */
    Route::post('logos/intent', [GenerationController::class, 'intent'])->middleware('throttle:30,1');
    Route::post('logos/generate', [GenerationController::class, 'generate'])->middleware('throttle:20,1');
    Route::get('logos/generation/{generation}', [GenerationController::class, 'show'])->middleware('throttle:120,1');

    Route::get('logos/{logo}', [LogoController::class, 'show']);
    Route::get('logos/{logo}/similar', [LogoController::class, 'similar']);
    Route::get('logos/{logo}/preview.svg', [LogoController::class, 'preview'])->name('api.logos.preview');

    Route::get('templates', [CatalogController::class, 'templates']);
    Route::get('templates/{template:slug}', [CatalogController::class, 'template']);
    Route::get('template-tokens/{token}', [CatalogController::class, 'templateByToken']);
    Route::get('industries', [CatalogController::class, 'industries']);
    Route::get('categories', [CatalogController::class, 'categories']);
    Route::get('styles', [CatalogController::class, 'styles']);
    Route::get('fonts', [CatalogController::class, 'fonts']);
    Route::get('palettes', [CatalogController::class, 'palettes']);
    Route::get('symbols', [CatalogController::class, 'symbols']);
    Route::get('layouts', [CatalogController::class, 'layouts']);
    Route::get('plans', [CatalogController::class, 'plans']);
    Route::get('showcase', [CatalogController::class, 'showcase']);
    Route::get('contact-info', [CatalogController::class, 'contactInfo']);
    Route::get('contact', [CatalogController::class, 'contactInfo']);

    Route::get('design-formats', [DesignFormatController::class, 'index']);
    Route::get('design-formats/{slug}', [DesignFormatController::class, 'show']);
    Route::post('design-formats/{slug}/generate', [DesignFormatController::class, 'generate'])->middleware('throttle:120,1');
    Route::post('design-formats/export', [DesignFormatController::class, 'export'])->middleware('throttle:60,1');

    Route::get('render/template/{template:slug}.svg', [RenderController::class, 'template'])->name('api.render.template');

    Route::post('designs/preview-mockups', [DesignController::class, 'mockups'])->middleware('throttle:60,1');
    Route::post('designs/audit', [DesignController::class, 'audit'])->middleware('throttle:60,1');
    Route::post('designs/web-icons', [DesignController::class, 'webIcons'])->middleware('throttle:30,1');

    Route::get('downloads/{download}', [DownloadController::class, 'show'])
        ->middleware('signed')
        ->name('api.downloads.show');

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    /*
    |--------------------------------------------------------------------------
    | Authenticated
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('designs', [DesignController::class, 'index']);
        Route::post('designs', [DesignController::class, 'store']);
        Route::get('designs/{design}', [DesignController::class, 'show']);
        Route::put('designs/{design}', [DesignController::class, 'update']);
        Route::patch('designs/{design}', [DesignController::class, 'update']);
        Route::delete('designs/{design}', [DesignController::class, 'destroy']);
        Route::post('designs/{design}/export', [DesignController::class, 'export']);
        Route::post('designs/{design}/ai-edit', [DesignController::class, 'aiEdit']);

        Route::post('logos/{logo}/save', [DesignController::class, 'saveFromLogo']);

        Route::get('favorites', [FavoriteController::class, 'index']);
        Route::post('favorites', [FavoriteController::class, 'store']);
        Route::post('favorites/toggle', [FavoriteController::class, 'toggle']);
        Route::delete('favorites/{favorite}', [FavoriteController::class, 'destroy']);

        Route::get('user/dashboard', DashboardController::class);

        Route::get('orders', [OrderController::class, 'index']);
        Route::post('orders', [OrderController::class, 'store'])->middleware('throttle:20,1');

        /*
        |--------------------------------------------------------------------------
        | Admin Routes (admin middleware + controller guard)
        |--------------------------------------------------------------------------
        */
        Route::prefix('admin')->middleware('admin')->group(function () {
            Route::get('stats', [AdminController::class, 'stats']);

            Route::get('users', [AdminController::class, 'users']);
            Route::patch('users/{user}/toggle-admin', [AdminController::class, 'toggleAdmin']);
            Route::delete('users/{user}', [AdminController::class, 'deleteUser']);

            Route::get('designs', [AdminController::class, 'designs']);
            Route::delete('designs/{design}', [AdminController::class, 'deleteDesign']);

            Route::get('assets', [AdminController::class, 'assets']);
            Route::post('assets', [AdminController::class, 'uploadAsset']);
            Route::patch('assets/{asset}', [AdminController::class, 'updateAsset']);
            Route::delete('assets/{asset}', [AdminController::class, 'deleteAsset']);

            Route::get('industries', [AdminController::class, 'industriesIndex']);
            Route::get('styles', [AdminController::class, 'stylesIndex']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Provider callbacks
    |--------------------------------------------------------------------------
    */
    Route::post('webhooks/payments/{provider}', [OrderController::class, 'webhook'])
        ->middleware('throttle:120,1');
});

require __DIR__.'/social-templates.php';
require __DIR__.'/admin.php';
