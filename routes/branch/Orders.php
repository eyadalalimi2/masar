<?php

use App\Http\Controllers\Orders\Branch\BranchOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:branch', 'ensure.branch'])->group(function () {
    Route::get('/branch/orders', [BranchOrderController::class, 'index'])->name('branch.orders.index');
    Route::post('/branch/orders/delay-alerts/generate', [BranchOrderController::class, 'generateDelayAlerts'])
        ->name('branch.orders.delay-alerts.generate');
    Route::get('/branch/orders/{order}', [BranchOrderController::class, 'show'])->name('branch.orders.show');
    Route::patch('/branch/orders/{order}/assign-distributor', [BranchOrderController::class, 'assignDistributor'])
        ->name('branch.orders.assign-distributor');
    Route::patch('/branch/orders/{order}/smart-dispatch', [BranchOrderController::class, 'smartDispatch'])
        ->name('branch.orders.smart-dispatch');
    Route::patch('/branch/orders/{order}/status', [BranchOrderController::class, 'changeStatus'])
        ->name('branch.orders.status');
    Route::patch('/branch/orders/{order}/reject', [BranchOrderController::class, 'reject'])
        ->name('branch.orders.reject');
});
