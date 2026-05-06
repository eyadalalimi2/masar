<?php

use App\Http\Controllers\Admin\Admin\AdminLocationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:admin', 'ensure.admin', 'ensure.admin.session', 'ensure.admin.permission', 'admin.audit'])->group(function () {
    Route::get('locations', [AdminLocationController::class, 'index'])->name('admin.locations.index');
    Route::post('locations', [AdminLocationController::class, 'store'])->name('admin.locations.store');
    Route::put('locations/{location}', [AdminLocationController::class, 'update'])->name('admin.locations.update');
    Route::delete('locations/{location}', [AdminLocationController::class, 'destroy'])->name('admin.locations.destroy');
});
