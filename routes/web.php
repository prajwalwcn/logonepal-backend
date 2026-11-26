<?php

use App\Http\Controllers\SandboxCheckoutController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'api' => url('/api/v1'),
]));

Route::middleware('signed')->group(function () {
    Route::get('payments/sandbox/{order}', [SandboxCheckoutController::class, 'show'])
        ->name('payments.sandbox.checkout');

    Route::post('payments/sandbox/{order}', [SandboxCheckoutController::class, 'confirm'])
        ->name('payments.sandbox.confirm');
});
