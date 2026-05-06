<?php

use App\Http\Controllers\Distribution\Admin\AdminBranchController;
use App\Http\Controllers\Distribution\Admin\AdminDistributorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:admin', 'ensure.admin', 'ensure.admin.session', 'ensure.admin.permission', 'admin.audit'])->group(function () {
    Route::resource('branches', AdminBranchController::class, [
        'as' => 'admin',
    ]);

    Route::patch('branches/{branch}/toggle', [AdminBranchController::class, 'toggle'])
        ->name('admin.branches.toggle');

    Route::patch('branches/{branch}/restore', [AdminBranchController::class, 'restore'])
        ->name('admin.branches.restore');

    Route::delete('branches/{branch}/force', [AdminBranchController::class, 'forceDelete'])
        ->name('admin.branches.force-delete');

    Route::resource('distributors', AdminDistributorController::class, [
        'as' => 'admin',
    ]);

    Route::patch('distributors/{distributor}/toggle', [AdminDistributorController::class, 'toggle'])
        ->name('admin.distributors.toggle');

    Route::patch('distributors/{distributor}/restore', [AdminDistributorController::class, 'restore'])
        ->name('admin.distributors.restore');

    Route::delete('distributors/{distributor}/force', [AdminDistributorController::class, 'forceDelete'])
        ->name('admin.distributors.force-delete');
});
