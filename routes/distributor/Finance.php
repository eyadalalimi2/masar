<?php

use App\Http\Controllers\Finance\Distributor\DistributorFinanceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:distributor', 'ensure.distributor'])->group(function () {
    Route::get('/distributor/payments', [DistributorFinanceController::class, 'payments'])->name('distributor.payments.index');
    Route::get('/distributor/payments/create', [DistributorFinanceController::class, 'createPayment'])->name('distributor.payments.create');
    Route::post('/distributor/payments', [DistributorFinanceController::class, 'storePayment'])->name('distributor.payments.store');
});


