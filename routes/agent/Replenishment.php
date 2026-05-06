<?php

use App\Http\Controllers\Distribution\Agent\AgentReplenishmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:agent', 'ensure.supplier'])->group(function () {
    Route::get('replenishment-requests', [AgentReplenishmentController::class, 'index'])->name('agent.replenishment.index');
    Route::patch('replenishment-requests/{replenishment}/approve', [AgentReplenishmentController::class, 'approve'])->name('agent.replenishment.approve');
    Route::patch('replenishment-requests/{replenishment}/reject', [AgentReplenishmentController::class, 'reject'])->name('agent.replenishment.reject');
    Route::patch('replenishment-requests/{replenishment}/fulfill', [AgentReplenishmentController::class, 'fulfill'])->name('agent.replenishment.fulfill');
});
