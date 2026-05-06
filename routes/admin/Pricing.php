<?php

use App\Http\Controllers\Admin\Admin\AdminPricingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:admin', 'ensure.admin', 'ensure.admin.session', 'ensure.admin.permission', 'admin.audit'])->group(function () {
    Route::get('pricing', [AdminPricingController::class, 'index'])->name('admin.pricing.index');

    Route::post('pricing/rules', [AdminPricingController::class, 'storeRule'])->name('admin.pricing.rules.store');
    Route::put('pricing/rules/{rule}', [AdminPricingController::class, 'updateRule'])->name('admin.pricing.rules.update');
    Route::delete('pricing/rules/{rule}', [AdminPricingController::class, 'destroyRule'])->name('admin.pricing.rules.destroy');
    Route::post('pricing/rules/preview', [AdminPricingController::class, 'previewCommission'])
        ->name('admin.pricing.rules.preview');

    Route::post('pricing/plans', [AdminPricingController::class, 'storePlan'])->name('admin.pricing.plans.store');
    Route::put('pricing/plans/{plan}', [AdminPricingController::class, 'updatePlan'])->name('admin.pricing.plans.update');
    Route::delete('pricing/plans/{plan}', [AdminPricingController::class, 'destroyPlan'])->name('admin.pricing.plans.destroy');
});
