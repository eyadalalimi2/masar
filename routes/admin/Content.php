<?php

use App\Http\Controllers\Admin\Admin\AdminContentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:admin', 'ensure.admin', 'ensure.admin.session', 'ensure.admin.permission', 'admin.audit'])->group(function () {
    Route::get('content', [AdminContentController::class, 'index'])->name('admin.content.index');

    Route::post('content/banners', [AdminContentController::class, 'storeBanner'])->name('admin.content.banners.store');
    Route::put('content/banners/{banner}', [AdminContentController::class, 'updateBanner'])->name('admin.content.banners.update');
    Route::delete('content/banners/{banner}', [AdminContentController::class, 'destroyBanner'])->name('admin.content.banners.destroy');

    Route::post('content/broadcasts', [AdminContentController::class, 'storeBroadcast'])->name('admin.content.broadcasts.store');
    Route::put('content/broadcasts/{broadcast}', [AdminContentController::class, 'updateBroadcast'])->name('admin.content.broadcasts.update');
    Route::delete('content/broadcasts/{broadcast}', [AdminContentController::class, 'destroyBroadcast'])->name('admin.content.broadcasts.destroy');
});
