<?php

use App\Http\Controllers\Reports\Agent\AgentReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:agent', 'ensure.supplier'])->group(function () {
    Route::get('reports', function () {
        return redirect()->route('agent.reports.commercial-stores.index');
    })->name('agent.reports.index');

    Route::get('reports/commercial-stores', [AgentReportController::class, 'commercialStoresIndex'])
        ->name('agent.reports.commercial-stores.index');
    Route::get('reports/commercial-stores/export', [AgentReportController::class, 'commercialStoresExport'])
        ->name('agent.reports.commercial-stores.export');

    Route::get('reports/workshops', [AgentReportController::class, 'workshopsIndex'])
        ->name('agent.reports.workshops.index');
    Route::get('reports/workshops/export', [AgentReportController::class, 'workshopsExport'])
        ->name('agent.reports.workshops.export');

    Route::get('coverage', [AgentReportController::class, 'coverage'])->name('agent.coverage.index');
    Route::get('reports/forecast/advanced', [AgentReportController::class, 'advancedForecast'])
        ->name('agent.reports.forecast.advanced');
    Route::post('reports/alerts/low-demand', [AgentReportController::class, 'generateLowDemandAlerts'])
        ->name('agent.reports.alerts.low-demand');
});
