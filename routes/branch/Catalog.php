<?php

use App\Http\Controllers\Catalog\Branch\BranchProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:branch', 'ensure.branch', 'ensure.portal.permission:catalog.manage'])->group(function () {
    Route::resource('products', BranchProductController::class, ['as' => 'branch'])->only(['index', 'show']);
});
