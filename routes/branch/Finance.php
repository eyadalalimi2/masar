<?php

use App\Http\Controllers\Finance\Branch\BranchFinanceController;
use App\Http\Controllers\Finance\Branch\BranchPaymentMethodController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:branch', 'ensure.branch'])->group(function () {
    Route::get('/branch/payments', [BranchFinanceController::class, 'payments'])->name('branch.payments.index');
    Route::get('/branch/payments/create', [BranchFinanceController::class, 'createPayment'])->name('branch.payments.create');
    Route::post('/branch/payments', [BranchFinanceController::class, 'storePayment'])->name('branch.payments.store');

    Route::get('/branch/payment-methods', [BranchPaymentMethodController::class, 'index'])
        ->name('branch.payment-methods.index');
    Route::put('/branch/payment-methods', [BranchPaymentMethodController::class, 'update'])
        ->name('branch.payment-methods.update');
});
