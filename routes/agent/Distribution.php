<?php

use App\Http\Controllers\Distribution\Agent\AgentBranchController;
use App\Http\Controllers\Distribution\Agent\AgentDistributorController;
use App\Http\Controllers\Distribution\Agent\AgentSpreadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:agent', 'ensure.supplier'])->group(function () {
    Route::get('spread', [AgentSpreadController::class, 'index'])->name('agent.spread.index');

    Route::resource('branches', AgentBranchController::class, [
        'as' => 'agent',
    ]);

    Route::patch('branches/{branch}/toggle', [AgentBranchController::class, 'toggle'])
        ->name('agent.branches.toggle');

    Route::resource('distributors', AgentDistributorController::class, [
        'as' => 'agent',
    ]);

    Route::patch('distributors/{distributor}/toggle', [AgentDistributorController::class, 'toggle'])
        ->name('agent.distributors.toggle');
});
