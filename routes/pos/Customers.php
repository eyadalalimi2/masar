<?php

use App\Http\Controllers\Pos\CustomerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:pos', 'ensure.pos'])->group(function () {
    Route::get('/pos/customers', [CustomerController::class, 'index'])->name('pos.customers.index');
    Route::post('/pos/customers', [CustomerController::class, 'store'])->name('pos.customers.store');
});
