<?php

use App\Http\Controllers\Catalog\Admin\AdminCategoryController;
use App\Http\Controllers\Catalog\Admin\AdminProductionYearController;
use App\Http\Controllers\Catalog\Admin\AdminProductController;
use App\Http\Controllers\Catalog\Admin\AdminUnitController;
use App\Http\Controllers\Catalog\Admin\AdminVariantTypeController;
use App\Http\Controllers\Catalog\Admin\AdminVariantValueController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:admin', 'ensure.admin', 'ensure.admin.session', 'ensure.admin.permission', 'admin.audit'])->group(function () {
    Route::resource('categories', AdminCategoryController::class, ['as' => 'admin'])->except('show');
    Route::resource('production-years', AdminProductionYearController::class, ['as' => 'admin'])->except('show');
    Route::resource('units', AdminUnitController::class, ['as' => 'admin'])->except('show');
    Route::resource('variant-types', AdminVariantTypeController::class, ['as' => 'admin'])->except('show');
    Route::resource('variant-values', AdminVariantValueController::class, ['as' => 'admin'])->except('show');

    Route::resource('products', AdminProductController::class, ['as' => 'admin']);
    Route::patch('products/{product}/toggle', [AdminProductController::class, 'toggle'])
        ->name('admin.products.toggle');
    Route::patch('products/{product}/restore', [AdminProductController::class, 'restore'])
        ->name('admin.products.restore');
    Route::delete('products/{product}/force', [AdminProductController::class, 'forceDelete'])
        ->name('admin.products.force-delete');
});
