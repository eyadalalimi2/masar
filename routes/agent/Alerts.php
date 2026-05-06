<?php

use App\Http\Controllers\Agent\AlertController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:agent', 'ensure.supplier'])->group(function () {
    Route::get('alerts', [AlertController::class, 'index'])->name('agent.alerts.index');
    Route::patch('alerts/mark-all-read', [AlertController::class, 'markAllAsRead'])->name('agent.alerts.mark-all');
    Route::patch('alerts/{alert}/mark-read', [AlertController::class, 'markAsRead'])->name('agent.alerts.mark-read');
});
