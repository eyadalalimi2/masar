<?php

use App\Http\Controllers\Pos\MarketplaceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:pos', 'ensure.pos'])->group(function () {
    Route::get('/pos/marketplace', [MarketplaceController::class, 'index'])->name('pos.marketplace.index');
    Route::post('/pos/marketplace/order', [MarketplaceController::class, 'storeOrder'])->name('pos.marketplace.order');
    Route::post('/pos/marketplace/cart/add', [MarketplaceController::class, 'addToCart'])->name('pos.marketplace.cart.add');
    Route::delete('/pos/marketplace/cart/{key}', [MarketplaceController::class, 'removeFromCart'])->name('pos.marketplace.cart.remove');
    Route::delete('/pos/marketplace/cart', [MarketplaceController::class, 'clearCart'])->name('pos.marketplace.cart.clear');
    Route::post('/pos/marketplace/cart/checkout', [MarketplaceController::class, 'checkoutCart'])->name('pos.marketplace.cart.checkout');
});
