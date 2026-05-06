<?php

use App\Http\Controllers\Pos\AlertController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:pos', 'ensure.pos'])->group(function () {
    Route::get('/pos/alerts', [AlertController::class, 'index'])->name('pos.alerts.index');
    Route::patch('/pos/alerts/mark-all-read', [AlertController::class, 'markAllAsRead'])->name('pos.alerts.mark-all');
    Route::patch('/pos/alerts/{alert}/mark-read', [AlertController::class, 'markAsRead'])->name('pos.alerts.mark-read');
});
