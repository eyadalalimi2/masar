<?php

use App\Http\Controllers\Customer\Agent\AgentCustomerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:agent', 'ensure.supplier'])->group(function () {
    Route::get('customers', function () {
        return redirect()->route('agent.dashboard');
    })->name('agent.customers.index');

    Route::prefix('commercial-stores')
        ->name('agent.commercial-stores.')
        ->middleware('ensure.portal.permission:customers.commercial.manage')
        ->group(function () {
            Route::get('/', [AgentCustomerController::class, 'commercialStoresIndex'])->name('index');
            Route::get('/create', [AgentCustomerController::class, 'commercialStoresCreate'])->name('create');
            Route::post('/', [AgentCustomerController::class, 'commercialStoresStore'])->name('store');
            Route::get('/{customer}/edit', [AgentCustomerController::class, 'commercialStoresEdit'])->name('edit');
            Route::put('/{customer}', [AgentCustomerController::class, 'commercialStoresUpdate'])->name('update');
            Route::delete('/{customer}', [AgentCustomerController::class, 'commercialStoresDestroy'])->name('destroy');
            Route::patch('/{customer}/toggle-status', [AgentCustomerController::class, 'commercialStoresToggleStatus'])->name('toggleStatus');
        });

    Route::prefix('workshops')
        ->name('agent.workshops.')
        ->middleware('ensure.portal.permission:customers.workshop.manage')
        ->group(function () {
            Route::get('/', [AgentCustomerController::class, 'workshopsIndex'])->name('index');
            Route::get('/create', [AgentCustomerController::class, 'workshopsCreate'])->name('create');
            Route::post('/', [AgentCustomerController::class, 'workshopsStore'])->name('store');
            Route::get('/{customer}/edit', [AgentCustomerController::class, 'workshopsEdit'])->name('edit');
            Route::put('/{customer}', [AgentCustomerController::class, 'workshopsUpdate'])->name('update');
            Route::delete('/{customer}', [AgentCustomerController::class, 'workshopsDestroy'])->name('destroy');
            Route::patch('/{customer}/toggle-status', [AgentCustomerController::class, 'workshopsToggleStatus'])->name('toggleStatus');
        });

    Route::prefix('wholesale-traders')
        ->name('agent.wholesale-traders.')
        ->middleware('ensure.portal.permission:customers.commercial.manage')
        ->group(function () {
            Route::get('/', [AgentCustomerController::class, 'wholesaleTradersIndex'])->name('index');
            Route::get('/create', [AgentCustomerController::class, 'wholesaleTradersCreate'])->name('create');
            Route::post('/', [AgentCustomerController::class, 'wholesaleTradersStore'])->name('store');
            Route::get('/{customer}/edit', [AgentCustomerController::class, 'wholesaleTradersEdit'])->name('edit');
            Route::put('/{customer}', [AgentCustomerController::class, 'wholesaleTradersUpdate'])->name('update');
            Route::delete('/{customer}', [AgentCustomerController::class, 'wholesaleTradersDestroy'])->name('destroy');
            Route::patch('/{customer}/toggle-status', [AgentCustomerController::class, 'wholesaleTradersToggleStatus'])->name('toggleStatus');
        });
});
