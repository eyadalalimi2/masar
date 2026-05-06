<?php

use App\Http\Controllers\Orders\Distributor\DistributorOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:distributor', 'ensure.distributor'])->group(function () {
    Route::get('/distributor/orders', [DistributorOrderController::class, 'index'])->name('distributor.orders.index');
    Route::post('/distributor/orders/delay-alerts/generate', [DistributorOrderController::class, 'generateDelayAlerts'])
        ->name('distributor.orders.delay-alerts.generate');
    Route::post('/distributor/orders/offline-sync', [DistributorOrderController::class, 'syncOfflineEvents'])
        ->name('distributor.orders.offline-sync');
    Route::get('/distributor/orders/route-optimization', [DistributorOrderController::class, 'routeOptimization'])
        ->name('distributor.orders.route-optimization');
    Route::get('/distributor/orders/{order}', [DistributorOrderController::class, 'show'])->name('distributor.orders.show');
    Route::patch('/distributor/orders/{order}/status', [DistributorOrderController::class, 'changeStatus'])
        ->name('distributor.orders.status');
    Route::post('/distributor/orders/{order}/location', [DistributorOrderController::class, 'updateLocation'])
        ->name('distributor.orders.location');
});
