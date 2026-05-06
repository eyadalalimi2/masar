<?php

use App\Http\Controllers\Orders\Admin\AdminOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:admin', 'ensure.admin', 'ensure.admin.session', 'ensure.admin.permission', 'admin.audit'])->group(function () {
    Route::get('orders', [AdminOrderController::class, 'index'])->name('admin.orders.index');
    Route::post('orders/delay-alerts/generate', [AdminOrderController::class, 'generateDelayAlerts'])
        ->name('admin.orders.delay-alerts.generate');
    Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('admin.orders.show');
    Route::delete('orders/{order}', [AdminOrderController::class, 'destroy'])->name('admin.orders.destroy');
    Route::patch('orders/{order}/restore', [AdminOrderController::class, 'restore'])->name('admin.orders.restore');
    Route::delete('orders/{order}/force', [AdminOrderController::class, 'forceDelete'])->name('admin.orders.force-delete');
    Route::patch('orders/{order}/smart-dispatch', [AdminOrderController::class, 'smartDispatch'])
        ->name('admin.orders.smart-dispatch');
    Route::patch('orders/{order}/status', [AdminOrderController::class, 'changeStatus'])->name('admin.orders.status');
});
