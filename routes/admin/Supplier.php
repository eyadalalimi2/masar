<?php

use App\Http\Controllers\Supplier\Admin\AdminSupplierController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:admin', 'ensure.admin', 'ensure.admin.session', 'ensure.admin.permission', 'admin.audit'])->group(function () {
    Route::resource('suppliers', AdminSupplierController::class, [
        'as' => 'admin',
    ]);

    Route::patch('suppliers/{supplier}/toggle', [AdminSupplierController::class, 'toggle'])
        ->name('admin.suppliers.toggle');

    Route::patch('suppliers/{supplier}/restore', [AdminSupplierController::class, 'restore'])
        ->name('admin.suppliers.restore');

    Route::delete('suppliers/{supplier}/force', [AdminSupplierController::class, 'forceDelete'])
        ->name('admin.suppliers.force-delete');

    Route::patch('suppliers/{supplier}/verify', [AdminSupplierController::class, 'verify'])
        ->name('admin.suppliers.verify');

    Route::patch('suppliers/{supplier}/field-change-requests/{changeRequest}/approve', [AdminSupplierController::class, 'approveFieldChange'])
        ->name('admin.suppliers.field-change-requests.approve');

    Route::patch('suppliers/{supplier}/field-change-requests/{changeRequest}/reject', [AdminSupplierController::class, 'rejectFieldChange'])
        ->name('admin.suppliers.field-change-requests.reject');
});
