<?php

use App\Http\Controllers\Branch\AlertController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:branch', 'ensure.branch'])->group(function () {
    Route::get('/branch/alerts', [AlertController::class, 'index'])->name('branch.alerts.index');
    Route::patch('/branch/alerts/mark-all-read', [AlertController::class, 'markAllAsRead'])->name('branch.alerts.mark-all');
    Route::patch('/branch/alerts/{alert}/mark-read', [AlertController::class, 'markAsRead'])->name('branch.alerts.mark-read');
});
