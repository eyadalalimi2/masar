<?php

use App\Http\Controllers\Distribution\Agent\AgentInventoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:agent', 'ensure.supplier'])->group(function () {
    Route::get('inventory', [AgentInventoryController::class, 'index'])->name('agent.inventory.index');
    Route::post('inventory/add-stock', [AgentInventoryController::class, 'addStock'])->name('agent.inventory.add-stock');
    Route::post('inventory/adjust-stock', [AgentInventoryController::class, 'adjustStock'])->name('agent.inventory.adjust-stock');
    Route::post('inventory/distribute', [AgentInventoryController::class, 'distribute'])->name('agent.inventory.distribute');
});
