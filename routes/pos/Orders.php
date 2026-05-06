<?php

use App\Http\Controllers\Pos\OrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:pos', 'ensure.pos'])->group(function () {
    Route::get('/pos/orders', [OrderController::class, 'index'])->name('pos.orders.index');
    Route::get('/pos/orders/{order}', [OrderController::class, 'show'])->name('pos.orders.show');
});
