<?php

use App\Http\Controllers\Branch\DistributorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:branch', 'ensure.branch'])->group(function () {
    Route::get('/branch/distributors', [DistributorController::class, 'index'])->name('branch.distributors.index');
    Route::post('/branch/distributors', [DistributorController::class, 'store'])->name('branch.distributors.store');
    Route::put('/branch/distributors/{distributor}', [DistributorController::class, 'update'])->name('branch.distributors.update');
    Route::patch('/branch/distributors/{distributor}/toggle', [DistributorController::class, 'toggle'])->name('branch.distributors.toggle');
});
