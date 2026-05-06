<?php

use App\Http\Controllers\Finance\Admin\AdminFinanceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:admin', 'ensure.admin', 'ensure.admin.session', 'ensure.admin.permission', 'admin.audit'])->group(function () {
    Route::get('payments', [AdminFinanceController::class, 'payments'])->name('admin.payments.index');
    Route::delete('payments/{payment}', [AdminFinanceController::class, 'destroyPayment'])->name('admin.payments.destroy');
    Route::patch('payments/{payment}/restore', [AdminFinanceController::class, 'restorePayment'])->name('admin.payments.restore');
    Route::delete('payments/{payment}/force', [AdminFinanceController::class, 'forceDeletePayment'])->name('admin.payments.force-delete');

    Route::get('accounts', [AdminFinanceController::class, 'accounts'])->name('admin.accounts.index');
    Route::delete('transactions/{transaction}', [AdminFinanceController::class, 'destroyTransaction'])->name('admin.transactions.destroy');
    Route::patch('transactions/{transaction}/restore', [AdminFinanceController::class, 'restoreTransaction'])->name('admin.transactions.restore');
    Route::delete('transactions/{transaction}/force', [AdminFinanceController::class, 'forceDeleteTransaction'])->name('admin.transactions.force-delete');
});
