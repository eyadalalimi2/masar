<?php

use App\Http\Controllers\Agent\AuthController as AgentSupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AgentSupplierController::class, 'showLoginForm'])->name('agent.login');
Route::post('/login', [AgentSupplierController::class, 'login'])->middleware('throttle:agent-login')->name('agent.login.submit');

Route::middleware(['auth:agent', 'ensure.supplier'])->group(function () {
    Route::get('/dashboard', [AgentSupplierController::class, 'dashboard'])->name('agent.dashboard');
    Route::get('/developer-profile', function () {
        return view('agent.developer-profile.index');
    })->name('agent.developer-profile.index');
    Route::get('/platform-release', function () {
        return view('agent.platform-release.index', [
            'platformVersion' => env('APP_VERSION', '1.0.0'),
            'releaseDate' => now()->format('Y-m-d'),
            'environmentName' => app()->environment(),
            'laravelVersion' => app()->version(),
            'phpVersion' => PHP_VERSION,
        ]);
    })->name('agent.platform-release.index');
    Route::get('/profile', [AgentSupplierController::class, 'profile'])->name('agent.profile');
    Route::put('/profile', [AgentSupplierController::class, 'updateProfile'])->name('agent.profile.update');
    Route::get('/profile/verification', [AgentSupplierController::class, 'verification'])->name('agent.profile.verification');
    Route::put('/profile/verification', [AgentSupplierController::class, 'updateVerification'])->name('agent.profile.verification.update');
    Route::patch('/profile/security', [AgentSupplierController::class, 'updateSecurity'])
        ->name('agent.profile.update-security');
    Route::patch('/profile/working-hours', [AgentSupplierController::class, 'updateWorkingHours'])
        ->name('agent.profile.update-working-hours');
    Route::post('/profile/change-request', [AgentSupplierController::class, 'requestFieldChange'])
        ->name('agent.profile.request-field-change');
    Route::patch('/profile/request-verification', [AgentSupplierController::class, 'requestVerification'])
        ->name('agent.profile.request-verification');
    Route::post('/logout', [AgentSupplierController::class, 'logout'])->name('agent.logout');
});
