<?php

use App\Http\Controllers\Consumer\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/consumer/login', [AuthController::class, 'showLoginForm'])->name('consumer.login');
Route::post('/consumer/login', [AuthController::class, 'login'])->middleware('throttle:consumer-login')->name('consumer.login.submit');

Route::middleware(['auth:consumer', 'ensure.consumer'])->group(function () {
    Route::get('/consumer/dashboard', [AuthController::class, 'dashboard'])->name('consumer.dashboard');
    Route::post('/consumer/logout', [AuthController::class, 'logout'])->name('consumer.logout');
});
