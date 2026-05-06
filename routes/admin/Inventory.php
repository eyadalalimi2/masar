<?php

use App\Http\Controllers\Admin\Admin\AdminInventoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:admin', 'ensure.admin', 'ensure.admin.session', 'ensure.admin.permission', 'admin.audit'])->group(function () {
    Route::get('inventory', [AdminInventoryController::class, 'index'])->name('admin.inventory.index');
});
