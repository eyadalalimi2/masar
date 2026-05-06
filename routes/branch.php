<?php

use App\Http\Controllers\Branch\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/branch/login', [AuthController::class, 'showLoginForm'])->name('branch.login');
Route::post('/branch/login', [AuthController::class, 'login'])->middleware('throttle:branch-login')->name('branch.login.submit');

Route::middleware(['auth:branch', 'ensure.branch'])->group(function () {
    Route::get('/branch/dashboard', [AuthController::class, 'dashboard'])->name('branch.dashboard');
    Route::get('/branch/developer-profile', function () {
        return view('branch.developer-profile.index');
    })->name('branch.developer-profile.index');
    Route::get('/branch/profile', [AuthController::class, 'profile'])->name('branch.profile');
    Route::put('/branch/profile', [AuthController::class, 'updateProfile'])->name('branch.profile.update');
    Route::put('/branch/profile/working-hours', [AuthController::class, 'updateWorkingHours'])
        ->name('branch.profile.update-working-hours');
    Route::post('/branch/logout', [AuthController::class, 'logout'])->name('branch.logout');
});

foreach (glob(base_path('routes/branch/*.php')) as $routeFile) {
    require $routeFile;
}
