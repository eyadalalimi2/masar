<?php

use App\Http\Controllers\Branch\BranchUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:branch', 'ensure.branch'])->group(function () {
    Route::get('/branch/users', [BranchUserController::class, 'index'])->name('branch.users.index');
    Route::post('/branch/users', [BranchUserController::class, 'store'])->name('branch.users.store');
    Route::put('/branch/users/{user}', [BranchUserController::class, 'update'])->name('branch.users.update');
    Route::patch('/branch/users/{user}/toggle', [BranchUserController::class, 'toggle'])->name('branch.users.toggle');
    Route::delete('/branch/users/{user}', [BranchUserController::class, 'destroy'])->name('branch.users.destroy');
});
