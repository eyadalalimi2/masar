<?php

namespace App\Modules\Workshop\Repositories;

use App\Models\Workshop\WorkshopService;
use App\Models\Workshop\WorkshopServiceOrder;
use Illuminate\Database\Eloquent\Builder;

class EloquentWorkshopRepository implements WorkshopRepositoryInterface
{
    public function servicesQuery(): Builder
    {
        return WorkshopService::query();
    }

    public function serviceOrdersQuery(): Builder
    {
        return WorkshopServiceOrder::query();
    }
}
