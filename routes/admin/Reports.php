<?php

use App\Http\Controllers\Reports\Admin\AdminReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:admin', 'ensure.admin', 'ensure.admin.session', 'ensure.admin.permission', 'admin.audit'])->group(function () {
    Route::get('reports', [AdminReportController::class, 'index'])->name('admin.reports.index');
});
