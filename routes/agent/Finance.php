<?php

use App\Http\Controllers\Finance\Agent\AgentFinanceController;
use App\Http\Controllers\Finance\Agent\AgentPaymentMethodController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:agent', 'ensure.supplier'])->group(function () {
    Route::get('payments', function () {
        return redirect()->route('agent.payments.commercial-stores.index');
    })->name('agent.payments.index');

    Route::get('payments/create', function () {
        return redirect()->route('agent.payments.commercial-stores.create');
    })->name('agent.payments.create');

    Route::get('payments/commercial-stores', [AgentFinanceController::class, 'commercialStoresPayments'])
        ->name('agent.payments.commercial-stores.index');
    Route::get('payments/commercial-stores/create', [AgentFinanceController::class, 'createCommercialStoresPayment'])
        ->name('agent.payments.commercial-stores.create');
    Route::post('payments/commercial-stores', [AgentFinanceController::class, 'storeCommercialStoresPayment'])
        ->name('agent.payments.commercial-stores.store');

    Route::get('payments/workshops', [AgentFinanceController::class, 'workshopsPayments'])
        ->name('agent.payments.workshops.index');
    Route::get('payments/workshops/create', [AgentFinanceController::class, 'createWorkshopsPayment'])
        ->name('agent.payments.workshops.create');
    Route::post('payments/workshops', [AgentFinanceController::class, 'storeWorkshopsPayment'])
        ->name('agent.payments.workshops.store');

    Route::post('payments', [AgentFinanceController::class, 'storeCommercialStoresPayment'])->name('agent.payments.store');

    Route::get('accounts', function () {
        return redirect()->route('agent.accounts.commercial-stores.index');
    })->name('agent.accounts.index');

    Route::get('accounts/commercial-stores', [AgentFinanceController::class, 'commercialStoresAccounts'])
        ->name('agent.accounts.commercial-stores.index');

    Route::get('accounts/workshops', [AgentFinanceController::class, 'workshopsAccounts'])
        ->name('agent.accounts.workshops.index');

    Route::get('payment-methods', [AgentPaymentMethodController::class, 'index'])
        ->name('agent.payment-methods.index');
    Route::put('payment-methods', [AgentPaymentMethodController::class, 'update'])
        ->name('agent.payment-methods.update');
});
