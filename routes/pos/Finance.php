<?php

use App\Http\Controllers\Finance\Pos\PosPaymentMethodController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:pos', 'ensure.pos'])->group(function () {
    Route::get('/pos/payment-methods', [PosPaymentMethodController::class, 'index'])
        ->name('pos.payment-methods.index');
    Route::put('/pos/payment-methods', [PosPaymentMethodController::class, 'update'])
        ->name('pos.payment-methods.update');
});
