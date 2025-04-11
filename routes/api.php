<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\OIDCSessionController;
use App\Http\Controllers\ShopifyWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get(
    '/',
    function (Request $request) {

        Route::middleware(['role:admin'])->get('/admin/users', [AdminUserController::class, 'index']);
    }
)->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->post('/impersonate', [ImpersonationController::class, 'impersonate']);
Route::middleware('auth:sanctum')->get(
    '/api/userinfo',
    function (Request $request) {
        return response()->json(
            [
                'sub' => $request->user()->id,
                'email' => $request->user()->email,
                'name' => $request->user()->name,
            ]
        );
    }
);
Route::post('/session/init', [OIDCSessionController::class, 'handleCallback'])->middleware('web');

// Token refresh endpoints
Route::prefix('tokens')->group(function () {
    Route::post('/refresh', [\App\Http\Controllers\API\TokenRefreshController::class, 'refresh'])
        ->name('api.tokens.refresh');
    Route::post('/revoke', [\App\Http\Controllers\API\TokenRefreshController::class, 'revoke'])
        ->name('api.tokens.revoke');
});

// Shopify webhook routes
Route::post('/webhooks/shopify', [ShopifyWebhookController::class, 'handleWebhook'])
    ->middleware('shopify.webhook')
    ->name('webhooks.shopify');
