<?php

use App\Http\Controllers\Pos\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:pos', 'ensure.pos'])->group(function () {
    Route::get('/pos/reports', [ReportController::class, 'index'])->name('pos.reports.index');
    Route::get('/pos/reports/export', [ReportController::class, 'export'])->name('pos.reports.export');
});