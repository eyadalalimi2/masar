<?php

use App\Http\Controllers\Branch\ClientController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:branch', 'ensure.branch'])->group(function () {
    Route::get('/branch/clients', [ClientController::class, 'index'])->name('branch.clients.index');
});
