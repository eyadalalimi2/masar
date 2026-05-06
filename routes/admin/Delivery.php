<?php

use App\Http\Controllers\Admin\Admin\AdminDeliveryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:admin', 'ensure.admin', 'ensure.admin.session', 'ensure.admin.permission', 'admin.audit'])->group(function () {
    Route::get('delivery', [AdminDeliveryController::class, 'index'])->name('admin.delivery.index');
    Route::patch('delivery/orders/{order}/assign', [AdminDeliveryController::class, 'assignDistributor'])
        ->name('admin.delivery.orders.assign');
});
