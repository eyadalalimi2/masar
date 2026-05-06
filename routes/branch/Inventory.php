<?php

use App\Http\Controllers\Branch\InventoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:branch', 'ensure.branch'])->group(function () {
    Route::get('/branch/inventory', [InventoryController::class, 'index'])->name('branch.inventory.index');
    Route::post('/branch/inventory/update-stock', [InventoryController::class, 'updateStock'])->name('branch.inventory.update-stock');
    Route::post('/branch/inventory/update-price', [InventoryController::class, 'updatePrice'])->name('branch.inventory.update-price');
    Route::post('/branch/inventory/auto-reorder', [InventoryController::class, 'autoReorder'])->name('branch.inventory.auto-reorder');
});
