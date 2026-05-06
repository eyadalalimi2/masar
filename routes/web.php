<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'auth.portals')->name('login');
Route::redirect('/login', '/');
Route::view('/developer-profile', 'shared.developer-profile')->name('developer.profile');
require base_path('routes/branch.php');
require base_path('routes/distributor.php');
require base_path('routes/pos.php');

foreach (glob(base_path('routes/web/*.php')) as $routeFile) {
    require $routeFile;
}
