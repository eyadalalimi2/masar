<?php

namespace App\Modules\Delivery\Repositories;

use App\Models\Distribution\Branch;
use App\Models\Distribution\Distributor;
use App\Models\Distribution\DistributorLocationLog;
use Illuminate\Database\Eloquent\Builder;

class EloquentDeliveryRepository implements DeliveryRepositoryInterface
{
    public function distributorsQuery(): Builder
    {
        return Distributor::query();
    }

    public function branchesQuery(): Builder
    {
        return Branch::query();
    }

    public function locationLogsQuery(): Builder
    {
        return DistributorLocationLog::query();
    }
}
