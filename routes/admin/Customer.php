<?php

use App\Http\Controllers\Customer\Admin\AdminCustomerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:admin', 'ensure.admin', 'ensure.admin.session', 'ensure.admin.permission', 'admin.audit'])->group(function () {
    Route::get('customers', function () {
        return redirect()->route('admin.commercial-stores.index');
    })->name('admin.customers.index');

    Route::prefix('commercial-stores')->name('admin.commercial-stores.')->group(function () {
        Route::get('/', [AdminCustomerController::class, 'commercialStoresIndex'])->name('index');
        Route::get('/create', [AdminCustomerController::class, 'commercialStoresCreate'])->name('create');
        Route::post('/', [AdminCustomerController::class, 'commercialStoresStore'])->name('store');
        Route::get('/{customer}/edit', [AdminCustomerController::class, 'commercialStoresEdit'])->name('edit');
        Route::put('/{customer}', [AdminCustomerController::class, 'commercialStoresUpdate'])->name('update');
        Route::put('/{customer}/working-hours', [AdminCustomerController::class, 'commercialStoresUpdateWorkingHours'])
            ->name('updateWorkingHours');
        Route::delete('/{customer}/images/{imageIndex}', [AdminCustomerController::class, 'commercialStoresRemoveImage'])
            ->name('removeImage');
        Route::delete('/{customer}', [AdminCustomerController::class, 'commercialStoresDestroy'])->name('destroy');
        Route::patch('/{customer}/restore', [AdminCustomerController::class, 'commercialStoresRestore'])->name('restore');
        Route::delete('/{customer}/force', [AdminCustomerController::class, 'commercialStoresForceDestroy'])->name('forceDestroy');
        Route::patch('/{customer}/toggle-status', [AdminCustomerController::class, 'commercialStoresToggleStatus'])->name('toggleStatus');
    });

    Route::prefix('workshops')->name('admin.workshops.')->group(function () {
        Route::get('/', [AdminCustomerController::class, 'workshopsIndex'])->name('index');
        Route::get('/create', [AdminCustomerController::class, 'workshopsCreate'])->name('create');
        Route::post('/', [AdminCustomerController::class, 'workshopsStore'])->name('store');
        Route::get('/{customer}/edit', [AdminCustomerController::class, 'workshopsEdit'])->name('edit');
        Route::put('/{customer}', [AdminCustomerController::class, 'workshopsUpdate'])->name('update');
        Route::put('/{customer}/working-hours', [AdminCustomerController::class, 'workshopsUpdateWorkingHours'])
            ->name('updateWorkingHours');
        Route::delete('/{customer}', [AdminCustomerController::class, 'workshopsDestroy'])->name('destroy');
        Route::patch('/{customer}/restore', [AdminCustomerController::class, 'workshopsRestore'])->name('restore');
        Route::delete('/{customer}/force', [AdminCustomerController::class, 'workshopsForceDestroy'])->name('forceDestroy');
        Route::patch('/{customer}/toggle-status', [AdminCustomerController::class, 'workshopsToggleStatus'])->name('toggleStatus');
    });

    Route::prefix('wholesale-traders')->name('admin.wholesale-traders.')->group(function () {
        Route::get('/', [AdminCustomerController::class, 'wholesaleTradersIndex'])->name('index');
        Route::get('/create', [AdminCustomerController::class, 'wholesaleTradersCreate'])->name('create');
        Route::post('/', [AdminCustomerController::class, 'wholesaleTradersStore'])->name('store');
        Route::get('/{customer}/edit', [AdminCustomerController::class, 'wholesaleTradersEdit'])->name('edit');
        Route::put('/{customer}', [AdminCustomerController::class, 'wholesaleTradersUpdate'])->name('update');
        Route::put('/{customer}/working-hours', [AdminCustomerController::class, 'wholesaleTradersUpdateWorkingHours'])
            ->name('updateWorkingHours');
        Route::delete('/{customer}/images/{imageIndex}', [AdminCustomerController::class, 'wholesaleTradersRemoveImage'])
            ->name('removeImage');
        Route::delete('/{customer}', [AdminCustomerController::class, 'wholesaleTradersDestroy'])->name('destroy');
        Route::patch('/{customer}/restore', [AdminCustomerController::class, 'wholesaleTradersRestore'])->name('restore');
        Route::delete('/{customer}/force', [AdminCustomerController::class, 'wholesaleTradersForceDestroy'])->name('forceDestroy');
        Route::patch('/{customer}/toggle-status', [AdminCustomerController::class, 'wholesaleTradersToggleStatus'])->name('toggleStatus');
    });
});
