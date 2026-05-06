<?php

use App\Http\Controllers\Distributor\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/distributor/login', [AuthController::class, 'showLoginForm'])->name('distributor.login');
Route::post('/distributor/login', [AuthController::class, 'login'])->middleware('throttle:distributor-login')->name('distributor.login.submit');

Route::middleware(['auth:distributor', 'ensure.distributor'])->group(function () {
    Route::get('/distributor/dashboard', [AuthController::class, 'dashboard'])->name('distributor.dashboard');
    Route::get('/distributor/profile', [AuthController::class, 'profile'])->name('distributor.profile');
    Route::put('/distributor/profile', [AuthController::class, 'updateProfile'])->name('distributor.profile.update');
    Route::get('/distributor/developer-profile', function () {
        return view('distributor.developer-profile.index');
    })->name('distributor.developer-profile.index');
    Route::get('/distributor/products', [AuthController::class, 'products'])->name('distributor.products.index');
    Route::get('/distributor/products/{product}', [AuthController::class, 'showProduct'])->name('distributor.products.show');
    Route::post('/distributor/logout', [AuthController::class, 'logout'])->name('distributor.logout');
});

foreach (glob(base_path('routes/distributor/*.php')) as $routeFile) {
    require $routeFile;
}
