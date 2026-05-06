<?php

use App\Http\Controllers\Pos\SaleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:pos', 'ensure.pos'])->group(function () {
    Route::get('/pos/sales', [SaleController::class, 'index'])->name('pos.sales.index');
    Route::post('/pos/sales', [SaleController::class, 'store'])->name('pos.sales.store');
    Route::post('/pos/sales/quick', [SaleController::class, 'storeQuickSale'])->name('pos.sales.quick.store');
});
