<?php

use App\Http\Controllers\Customer\ConsumerPortalAuthController;
use App\Http\Controllers\Customer\CustomerPortalAuthController;
use App\Http\Controllers\Customer\CustomerPortalController;
use App\Http\Controllers\Customer\PosPortalAuthController;
use App\Http\Controllers\Customer\WorkshopPortalAuthController;
use App\Http\Controllers\Pdf\PortalPdfTemplateController;
use App\Http\Controllers\Consumer\ConsumerAppController;
use App\Http\Controllers\Finance\Workshop\WorkshopPaymentMethodController;
use App\Http\Controllers\Workshop\WorkshopAppointmentController;
use App\Http\Controllers\Workshop\WorkshopInsightsController;
use App\Http\Controllers\Workshop\WorkshopMarketplaceController;
use App\Http\Controllers\Catalog\Workshop\WorkshopProductController;
use App\Http\Controllers\Workshop\WorkshopPurchaseOrderController;
use App\Http\Controllers\Workshop\WorkshopServiceController;
use App\Http\Controllers\Workshop\WorkshopServiceOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/customer/login', [CustomerPortalAuthController::class, 'showLoginForm'])->name('customer.login');
Route::post('/customer/login', [CustomerPortalAuthController::class, 'login'])->middleware('throttle:customer-login')->name('customer.login.submit');

Route::middleware(['auth:customer', 'ensure.customer'])->group(function () {
    Route::get('/customer/dashboard', [CustomerPortalAuthController::class, 'dashboard'])->name('customer.dashboard');
    Route::get('/customer/orders', [CustomerPortalController::class, 'orders'])->name('customer.orders.index');
    Route::get('/customer/payments', [CustomerPortalController::class, 'payments'])->name('customer.payments.index');
    Route::get('/customer/wholesale/products', [CustomerPortalController::class, 'wholesaleProducts'])
        ->name('customer.wholesale.products.index');
    Route::get('/customer/wholesale/products/create', [CustomerPortalController::class, 'wholesaleProductCreate'])
        ->name('customer.wholesale.products.create');
    Route::post('/customer/wholesale/products', [CustomerPortalController::class, 'wholesaleProductStore'])
        ->name('customer.wholesale.products.store');
    Route::get('/customer/wholesale/orders', [CustomerPortalController::class, 'wholesaleOrders'])
        ->name('customer.wholesale.orders.index');
    Route::patch('/customer/wholesale/orders/{order}/status', [CustomerPortalController::class, 'updateWholesaleOrderStatus'])
        ->name('customer.wholesale.orders.status');
    Route::get('/customer/wholesale/customers', [CustomerPortalController::class, 'wholesaleCustomers'])
        ->name('customer.wholesale.customers.index');
    Route::get('/customer/payment-methods', [CustomerPortalController::class, 'wholesalePaymentMethods'])
        ->name('customer.payment-methods.index');
    Route::put('/customer/payment-methods', [CustomerPortalController::class, 'updateWholesalePaymentMethods'])
        ->name('customer.payment-methods.update');
    Route::get('/customer/profile', [CustomerPortalController::class, 'profile'])->name('customer.profile.index');
    Route::put('/customer/profile', [CustomerPortalController::class, 'updateProfile'])->name('customer.profile.update');
    Route::get('/customer/profile/verification', [CustomerPortalController::class, 'verification'])
        ->name('customer.profile.verification');
    Route::put('/customer/profile/verification', [CustomerPortalController::class, 'updateVerification'])
        ->name('customer.profile.verification.update');
    Route::patch('/customer/profile/request-verification', [CustomerPortalController::class, 'requestVerification'])
        ->name('customer.profile.request-verification');
    Route::delete('/customer/profile/store-images/{imageIndex}', [CustomerPortalController::class, 'destroyStoreImage'])
        ->name('customer.profile.store-images.destroy');
    Route::put('/customer/profile/working-hours', [CustomerPortalController::class, 'updateWorkingHours'])
        ->name('customer.profile.update-working-hours');
    Route::get('/customer/developer-profile', function () {
        return view('customer.developer-profile.index');
    })->name('customer.developer-profile.index');
    Route::post('/customer/logout', [CustomerPortalAuthController::class, 'logout'])->name('customer.logout');
});

Route::get('/consumer/login', [ConsumerPortalAuthController::class, 'showLoginForm'])->name('consumer.login');
Route::post('/consumer/login', [ConsumerPortalAuthController::class, 'login'])->middleware('throttle:consumer-login')->name('consumer.login.submit');

Route::middleware(['auth:consumer', 'ensure.consumer'])->group(function () {
    Route::get('/consumer/dashboard', [ConsumerAppController::class, 'dashboard'])->name('consumer.dashboard');
    Route::get('/consumer/settings/pdf-templates', [PortalPdfTemplateController::class, 'index'])
        ->defaults('scope', 'consumer')
        ->name('consumer.settings.pdf-templates.index');
    Route::put('/consumer/settings/pdf-templates', [PortalPdfTemplateController::class, 'update'])
        ->defaults('scope', 'consumer')
        ->name('consumer.settings.pdf-templates.update');
    Route::get('/consumer/settings/pdf-templates/preview', [PortalPdfTemplateController::class, 'preview'])
        ->defaults('scope', 'consumer')
        ->name('consumer.settings.pdf-templates.preview');
    Route::get('/consumer/home', [ConsumerAppController::class, 'home'])->name('consumer.home');
    Route::get('/consumer/browse', [ConsumerAppController::class, 'browse'])->name('consumer.browse');
    Route::get('/consumer/recommendations', [ConsumerAppController::class, 'recommendations'])
        ->name('consumer.recommendations');
    Route::get('/consumer/store/{storeType}/{storeId}', [ConsumerAppController::class, 'storeView'])
        ->whereIn('storeType', ['pos', 'workshop', 'retail'])
        ->name('consumer.store.show');

    Route::post('/consumer/orders/product', [ConsumerAppController::class, 'createProductOrder'])
        ->name('consumer.orders.product.store');
    Route::post('/consumer/orders/retail', [ConsumerAppController::class, 'createRetailOrder'])
        ->name('consumer.orders.retail.store');
    Route::post('/consumer/orders/service', [ConsumerAppController::class, 'createServiceOrder'])
        ->name('consumer.orders.service.store');

    Route::get('/consumer/tracking', [ConsumerAppController::class, 'tracking'])->name('consumer.tracking');
    Route::get('/consumer/history', [ConsumerAppController::class, 'history'])->name('consumer.history');
    Route::post('/consumer/history/reorder/{order}', [ConsumerAppController::class, 'reorderProduct'])
        ->name('consumer.history.reorder');
    Route::post('/consumer/history/reorder-service/{order}', [ConsumerAppController::class, 'reorderService'])
        ->name('consumer.history.reorder-service');

    Route::get('/consumer/addresses', [ConsumerAppController::class, 'addresses'])->name('consumer.addresses.index');
    Route::post('/consumer/addresses', [ConsumerAppController::class, 'storeAddress'])->name('consumer.addresses.store');
    Route::put('/consumer/addresses/{address}', [ConsumerAppController::class, 'updateAddress'])
        ->name('consumer.addresses.update');
    Route::patch('/consumer/addresses/{address}/default', [ConsumerAppController::class, 'setDefaultAddress'])
        ->name('consumer.addresses.default');
    Route::delete('/consumer/addresses/{address}', [ConsumerAppController::class, 'destroyAddress'])
        ->name('consumer.addresses.destroy');

    Route::get('/consumer/ratings', [ConsumerAppController::class, 'ratings'])->name('consumer.ratings.index');
    Route::post('/consumer/ratings', [ConsumerAppController::class, 'storeRating'])->name('consumer.ratings.store');

    Route::get('/consumer/profile', [ConsumerAppController::class, 'profile'])->name('consumer.profile.index');
    Route::put('/consumer/profile', [ConsumerAppController::class, 'updateProfile'])->name('consumer.profile.update');
    Route::post('/consumer/profile/vehicles', [ConsumerAppController::class, 'storeVehicle'])
        ->name('consumer.profile.vehicles.store');
    Route::put('/consumer/profile/vehicles/{vehicle}', [ConsumerAppController::class, 'updateVehicle'])
        ->name('consumer.profile.vehicles.update');
    Route::delete('/consumer/profile/vehicles/{vehicle}', [ConsumerAppController::class, 'destroyVehicle'])
        ->name('consumer.profile.vehicles.destroy');
    Route::patch('/consumer/profile/vehicles/{vehicle}/default', [ConsumerAppController::class, 'setDefaultVehicle'])
        ->name('consumer.profile.vehicles.default');
    Route::post('/consumer/alerts/read-all', [ConsumerAppController::class, 'markAllAlertsAsRead'])
        ->name('consumer.alerts.read-all');

    Route::get('/consumer/developer-profile', function () {
        return view('consumer.developer-profile.index');
    })->name('consumer.developer-profile.index');
    Route::post('/consumer/logout', [ConsumerPortalAuthController::class, 'logout'])->name('consumer.logout');
});

Route::get('/pos/login', [PosPortalAuthController::class, 'showLoginForm'])->name('pos.login');
Route::post('/pos/login', [PosPortalAuthController::class, 'login'])->middleware('throttle:pos-login')->name('pos.login.submit');

Route::middleware(['auth:pos', 'ensure.pos'])->group(function () {
    Route::get('/pos/dashboard', [PosPortalAuthController::class, 'dashboard'])->name('pos.dashboard');
    Route::get('/pos/profile', [PosPortalAuthController::class, 'profile'])->name('pos.profile.index');
    Route::put('/pos/profile', [PosPortalAuthController::class, 'updateProfile'])->name('pos.profile.update');
    Route::get('/pos/profile/verification', [PosPortalAuthController::class, 'verification'])->name('pos.profile.verification');
    Route::put('/pos/profile/verification', [PosPortalAuthController::class, 'updateVerification'])->name('pos.profile.verification.update');
    Route::patch('/pos/profile/request-verification', [PosPortalAuthController::class, 'requestVerification'])
        ->name('pos.profile.request-verification');
    Route::put('/pos/profile/working-hours', [PosPortalAuthController::class, 'updateWorkingHours'])
        ->name('pos.profile.update-working-hours');
    Route::get('/pos/developer-profile', function () {
        return view('pos.developer-profile.index');
    })->name('pos.developer-profile.index');
    Route::post('/pos/logout', [PosPortalAuthController::class, 'logout'])->name('pos.logout');
});

Route::get('/workshop/login', [WorkshopPortalAuthController::class, 'showLoginForm'])->name('workshop.login');
Route::post('/workshop/login', [WorkshopPortalAuthController::class, 'login'])->middleware('throttle:workshop-login')->name('workshop.login.submit');

Route::middleware(['auth:workshop', 'ensure.workshop'])->group(function () {
    Route::get('/workshop/dashboard', [WorkshopPortalAuthController::class, 'dashboard'])->name('workshop.dashboard');
    Route::get('/workshop/settings/pdf-templates', [PortalPdfTemplateController::class, 'index'])
        ->defaults('scope', 'workshop')
        ->name('workshop.settings.pdf-templates.index');
    Route::put('/workshop/settings/pdf-templates', [PortalPdfTemplateController::class, 'update'])
        ->defaults('scope', 'workshop')
        ->name('workshop.settings.pdf-templates.update');
    Route::get('/workshop/settings/pdf-templates/preview', [PortalPdfTemplateController::class, 'preview'])
        ->defaults('scope', 'workshop')
        ->name('workshop.settings.pdf-templates.preview');
    Route::get('/workshop/profile', [WorkshopPortalAuthController::class, 'profile'])->name('workshop.profile.index');
    Route::put('/workshop/profile', [WorkshopPortalAuthController::class, 'updateProfile'])->name('workshop.profile.update');
    Route::get('/workshop/profile/verification', [WorkshopPortalAuthController::class, 'verification'])->name('workshop.profile.verification');
    Route::put('/workshop/profile/verification', [WorkshopPortalAuthController::class, 'updateVerification'])->name('workshop.profile.verification.update');
    Route::patch('/workshop/profile/request-verification', [WorkshopPortalAuthController::class, 'requestVerification'])
        ->name('workshop.profile.request-verification');
    Route::put('/workshop/profile/working-hours', [WorkshopPortalAuthController::class, 'updateWorkingHours'])
        ->name('workshop.profile.update-working-hours');

    Route::get('/workshop/services', [WorkshopServiceController::class, 'index'])->name('workshop.services.index');
    Route::post('/workshop/services', [WorkshopServiceController::class, 'store'])->name('workshop.services.store');
    Route::put('/workshop/services/{service}', [WorkshopServiceController::class, 'update'])->name('workshop.services.update');
    Route::patch('/workshop/services/{service}/toggle', [WorkshopServiceController::class, 'toggle'])
        ->name('workshop.services.toggle');

    Route::get('/workshop/marketplace', [WorkshopMarketplaceController::class, 'index'])
        ->name('workshop.marketplace.index');
    Route::post('/workshop/marketplace/cart', [WorkshopMarketplaceController::class, 'addToCart'])
        ->name('workshop.marketplace.cart.add');
    Route::delete('/workshop/marketplace/cart/{stock}', [WorkshopMarketplaceController::class, 'removeFromCart'])
        ->name('workshop.marketplace.cart.remove');
    Route::delete('/workshop/marketplace/cart', [WorkshopMarketplaceController::class, 'clearCart'])
        ->name('workshop.marketplace.cart.clear');
    Route::post('/workshop/marketplace/cart/checkout', [WorkshopMarketplaceController::class, 'checkoutCart'])
        ->name('workshop.marketplace.cart.checkout');
    Route::post('/workshop/marketplace/order', [WorkshopMarketplaceController::class, 'storeOrder'])
        ->name('workshop.marketplace.order');

    Route::get('/workshop/orders/purchase', [WorkshopPurchaseOrderController::class, 'index'])
        ->name('workshop.orders.purchase.index');
    Route::post('/workshop/orders/purchase', [WorkshopPurchaseOrderController::class, 'store'])
        ->name('workshop.orders.purchase.store');
    Route::patch('/workshop/orders/purchase/{order}/status', [WorkshopPurchaseOrderController::class, 'updateStatus'])
        ->name('workshop.orders.purchase.status');

    Route::get('/workshop/orders/service', [WorkshopServiceOrderController::class, 'index'])
        ->name('workshop.orders.service.index');
    Route::post('/workshop/orders/service', [WorkshopServiceOrderController::class, 'store'])
        ->name('workshop.orders.service.store');
    Route::patch('/workshop/orders/service/{order}/status', [WorkshopServiceOrderController::class, 'updateStatus'])
        ->name('workshop.orders.service.status');
    Route::get('/workshop/maintenance/history', [WorkshopServiceOrderController::class, 'maintenanceHistory'])
        ->name('workshop.maintenance.history');

    Route::get('/workshop/appointments', [WorkshopAppointmentController::class, 'index'])
        ->name('workshop.appointments.index');
    Route::post('/workshop/appointments', [WorkshopAppointmentController::class, 'store'])
        ->name('workshop.appointments.store');
    Route::patch('/workshop/appointments/{appointment}/status', [WorkshopAppointmentController::class, 'updateStatus'])
        ->name('workshop.appointments.status');

    Route::get('/workshop/execution', [WorkshopInsightsController::class, 'execution'])
        ->name('workshop.execution.index');
    Route::post('/workshop/execution/sla-alerts/generate', [WorkshopInsightsController::class, 'generateSlaAlerts'])
        ->name('workshop.execution.sla-alerts.generate');
    Route::patch('/workshop/execution/{order}/products', [WorkshopServiceOrderController::class, 'updateUsedProducts'])
        ->name('workshop.execution.products.update');

    Route::get('/workshop/live/overview', [WorkshopInsightsController::class, 'liveOverview'])
        ->name('workshop.live.overview');

    Route::get('/workshop/sales', [WorkshopInsightsController::class, 'sales'])
        ->name('workshop.sales.index');
    Route::resource('/workshop/products', WorkshopProductController::class, ['as' => 'workshop'])
        ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::post('/workshop/products/{product}/duplicate', [WorkshopProductController::class, 'duplicate'])
        ->name('workshop.products.duplicate');
    Route::get('/workshop/sales/{order}/invoice', [WorkshopInsightsController::class, 'invoice'])
        ->name('workshop.sales.invoice');
    Route::get('/workshop/pricing', [WorkshopInsightsController::class, 'pricing'])
        ->name('workshop.pricing.index');
    Route::get('/workshop/customers', [WorkshopInsightsController::class, 'customers'])
        ->name('workshop.customers.index');
    Route::get('/workshop/reports', [WorkshopInsightsController::class, 'reports'])
        ->name('workshop.reports.index');
    Route::get('/workshop/payment-methods', [WorkshopPaymentMethodController::class, 'index'])
        ->name('workshop.payment-methods.index');
    Route::put('/workshop/payment-methods', [WorkshopPaymentMethodController::class, 'update'])
        ->name('workshop.payment-methods.update');
    Route::view('/workshop/developer-profile', 'workshop.developer-profile.index')->name('workshop.developer-profile.index');
    Route::post('/workshop/logout', [WorkshopPortalAuthController::class, 'logout'])->name('workshop.logout');
});
