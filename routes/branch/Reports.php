<?php

use App\Http\Controllers\Branch\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:branch', 'ensure.branch'])->group(function () {
    Route::get('/branch/reports', [ReportController::class, 'index'])->name('branch.reports.index');
    Route::get('/branch/reports/export', [ReportController::class, 'export'])->name('branch.reports.export');
});
