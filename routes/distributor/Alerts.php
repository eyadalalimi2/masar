<?php

use App\Http\Controllers\Distributor\AlertController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:distributor', 'ensure.distributor'])->group(function () {
    Route::get('/distributor/alerts', [AlertController::class, 'index'])->name('distributor.alerts.index');
    Route::patch('/distributor/alerts/mark-all-read', [AlertController::class, 'markAllAsRead'])->name('distributor.alerts.mark-all');
    Route::patch('/distributor/alerts/{alert}/mark-read', [AlertController::class, 'markAsRead'])->name('distributor.alerts.mark-read');
});
