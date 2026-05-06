<?php

use App\Http\Controllers\Branch\ReplenishmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:branch', 'ensure.branch'])->group(function () {
    Route::get('/branch/replenishment', [ReplenishmentController::class, 'index'])->name('branch.replenishment.index');
    Route::post('/branch/replenishment', [ReplenishmentController::class, 'store'])->name('branch.replenishment.store');
});
