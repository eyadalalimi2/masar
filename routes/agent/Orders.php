<?php

use App\Http\Controllers\Orders\Agent\AgentOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:agent', 'ensure.supplier'])->group(function () {
    Route::get('orders', [AgentOrderController::class, 'index'])->name('agent.orders.index');
    Route::post('orders/delay-alerts/generate', [AgentOrderController::class, 'generateDelayAlerts'])
        ->name('agent.orders.delay-alerts.generate');
    Route::get('orders/create', [AgentOrderController::class, 'create'])->name('agent.orders.create');
    Route::post('orders', [AgentOrderController::class, 'store'])->name('agent.orders.store');
    Route::get('orders/{order}', [AgentOrderController::class, 'show'])->name('agent.orders.show');
    Route::patch('orders/{order}/assign-distributor', [AgentOrderController::class, 'assignDistributor'])
        ->name('agent.orders.assignDistributor');
    Route::patch('orders/{order}/smart-dispatch', [AgentOrderController::class, 'smartDispatch'])
        ->name('agent.orders.smart-dispatch');
    Route::patch('orders/{order}/status', [AgentOrderController::class, 'changeStatus'])->name('agent.orders.status');
});
