<?php

use App\Http\Controllers\Api\Portal\UnifiedPortalApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->group(function () {
    foreach (['admin', 'agent', 'branch', 'distributor', 'pos', 'workshop', 'consumer'] as $portal) {
        Route::middleware('auth:' . $portal)->group(function () use ($portal) {
            Route::get('/' . $portal . '/me', [UnifiedPortalApiController::class, 'me'])
                ->defaults('portal', $portal)
                ->name('api.v1.' . $portal . '.me');

            Route::get('/' . $portal . '/overview', [UnifiedPortalApiController::class, 'overview'])
                ->defaults('portal', $portal)
                ->name('api.v1.' . $portal . '.overview');
        });
    }

    Route::middleware(['auth:pos', 'ensure.pos'])->group(function () {
        Route::get('/pos/sales/recent', [UnifiedPortalApiController::class, 'salesRecent'])
            ->name('api.v1.pos.sales.recent');
    });

    Route::middleware(['auth:workshop', 'ensure.workshop'])->group(function () {
        Route::get('/workshop/maintenance/history', [UnifiedPortalApiController::class, 'maintenanceHistory'])
            ->name('api.v1.workshop.maintenance.history');
    });
});
