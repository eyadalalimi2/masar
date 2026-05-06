<?php

use App\Http\Controllers\Catalog\Agent\AgentProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:agent', 'ensure.supplier'])->group(function () {
    Route::resource('products', AgentProductController::class, ['as' => 'agent']);
    Route::patch('products/bulk-pricing', [AgentProductController::class, 'bulkPricingUpdate'])
        ->name('agent.products.bulk-pricing');
    Route::patch('products/{product}/toggle', [AgentProductController::class, 'toggle'])
        ->name('agent.products.toggle');
});
