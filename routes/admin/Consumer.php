<?php

use App\Http\Controllers\Customer\Admin\AdminConsumerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:admin', 'ensure.admin', 'ensure.admin.session', 'ensure.admin.permission', 'admin.audit'])->group(function () {
    Route::get('consumers', [AdminConsumerController::class, 'index'])->name('admin.consumers.index');
    Route::get('consumers/create', [AdminConsumerController::class, 'create'])->name('admin.consumers.create');
    Route::post('consumers', [AdminConsumerController::class, 'store'])->name('admin.consumers.store');
    Route::get('consumers/{consumer}/edit', [AdminConsumerController::class, 'edit'])->name('admin.consumers.edit');
    Route::put('consumers/{consumer}', [AdminConsumerController::class, 'update'])->name('admin.consumers.update');
    Route::delete('consumers/{consumer}', [AdminConsumerController::class, 'destroy'])->name('admin.consumers.destroy');
    Route::patch('consumers/{consumer}/toggle-status', [AdminConsumerController::class, 'toggleStatus'])
        ->name('admin.consumers.toggleStatus');
});
