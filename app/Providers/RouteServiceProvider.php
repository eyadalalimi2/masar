<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        Route::prefix('admin')
            ->middleware(['web'])
            ->group(base_path('routes/admin.php'));

        Route::prefix('agent')
            ->middleware(['web'])
            ->group(base_path('routes/agent.php'));
    }
}
