<?php

namespace App\Providers;

use App\Modules\Accounting\Repositories\AccountingRepositoryInterface;
use App\Modules\Accounting\Repositories\EloquentAccountingRepository;
use App\Modules\Delivery\Repositories\DeliveryRepositoryInterface;
use App\Modules\Delivery\Repositories\EloquentDeliveryRepository;
use App\Modules\Inventory\Repositories\EloquentInventoryRepository;
use App\Modules\Inventory\Repositories\InventoryRepositoryInterface;
use App\Modules\Orders\Repositories\EloquentOrdersRepository;
use App\Modules\Orders\Repositories\OrdersRepositoryInterface;
use App\Modules\Pos\Repositories\EloquentPosRepository;
use App\Modules\Pos\Repositories\PosRepositoryInterface;
use App\Modules\Users\Repositories\EloquentUsersRepository;
use App\Modules\Users\Repositories\UsersRepositoryInterface;
use App\Modules\Workshop\Repositories\EloquentWorkshopRepository;
use App\Modules\Workshop\Repositories\WorkshopRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InventoryRepositoryInterface::class, EloquentInventoryRepository::class);
        $this->app->bind(AccountingRepositoryInterface::class, EloquentAccountingRepository::class);
        $this->app->bind(OrdersRepositoryInterface::class, EloquentOrdersRepository::class);
        $this->app->bind(DeliveryRepositoryInterface::class, EloquentDeliveryRepository::class);
        $this->app->bind(WorkshopRepositoryInterface::class, EloquentWorkshopRepository::class);
        $this->app->bind(PosRepositoryInterface::class, EloquentPosRepository::class);
        $this->app->bind(UsersRepositoryInterface::class, EloquentUsersRepository::class);
    }

    public function boot(): void
    {
        // Transitional provider for modular architecture; legacy namespaces remain valid.
    }
}
