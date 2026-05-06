<?php

use App\Http\Controllers\Supplier\Agent\AgentUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:agent', 'ensure.supplier'])->group(function () {
    Route::get('users', [AgentUserController::class, 'index'])->name('agent.users.index');
    Route::post('users', [AgentUserController::class, 'store'])->name('agent.users.store');
    Route::put('users/{user}', [AgentUserController::class, 'update'])->name('agent.users.update');
    Route::patch('users/{user}/toggle', [AgentUserController::class, 'toggle'])->name('agent.users.toggle');
    Route::delete('users/{user}', [AgentUserController::class, 'destroy'])->name('agent.users.destroy');
});
