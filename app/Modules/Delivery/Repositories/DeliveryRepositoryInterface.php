<?php

namespace App\Modules\Delivery\Repositories;

use Illuminate\Database\Eloquent\Builder;

interface DeliveryRepositoryInterface
{
    public function distributorsQuery(): Builder;

    public function branchesQuery(): Builder;

    public function locationLogsQuery(): Builder;
}
