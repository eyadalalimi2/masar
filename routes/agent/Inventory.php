<?php

use App\Http\Controllers\Distribution\Agent\AgentInventoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:agent', 'ensure.supplier'])->group(function () {
    Route::get('inventory', [AgentInventoryController::class, 'index'])->name('agent.inventory.index');
    Route::get('inventory/stock-management', [AgentInventoryController::class, 'stockManagement'])->name('agent.inventory.stock-management');
    Route::get('inventory/distribution', [AgentInventoryController::class, 'distributionPage'])->name('agent.inventory.distribution-page');
    Route::get('inventory/distribution/model-lookup', [AgentInventoryController::class, 'distributionModelLookup'])->name('agent.inventory.distribution.model-lookup');
    Route::get('inventory/movements', [AgentInventoryController::class, 'movements'])->name('agent.inventory.movements');
    Route::post('inventory/add-stock', [AgentInventoryController::class, 'addStock'])->name('agent.inventory.add-stock');
    Route::post('inventory/adjust-stock', [AgentInventoryController::class, 'adjustStock'])->name('agent.inventory.adjust-stock');
    Route::post('inventory/distribute', [AgentInventoryController::class, 'distribute'])->name('agent.inventory.distribute');
});
