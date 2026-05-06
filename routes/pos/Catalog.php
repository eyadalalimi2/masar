<?php

use App\Http\Controllers\Pos\CatalogController;
use App\Http\Controllers\Catalog\Pos\PosProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:pos', 'ensure.pos'])->group(function () {
    Route::get('/pos/catalog', [CatalogController::class, 'index'])->name('pos.catalog.index');
    Route::post('/pos/catalog/smart-refill-alerts/generate', [CatalogController::class, 'generateSmartRefillAlerts'])
        ->name('pos.catalog.smart-refill.generate');
    Route::patch('/pos/catalog/{localProduct}/price', [CatalogController::class, 'updatePrice'])->name('pos.catalog.price');
    Route::patch('/pos/catalog/{localProduct}/toggle', [CatalogController::class, 'toggle'])->name('pos.catalog.toggle');

    Route::resource('/pos/products', PosProductController::class, ['as' => 'pos'])
        ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::post('/pos/products/{product}/duplicate', [PosProductController::class, 'duplicate'])
        ->name('pos.products.duplicate');
});
