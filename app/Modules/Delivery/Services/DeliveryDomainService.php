<?php

namespace App\Modules\Delivery\Services;

use App\Modules\Delivery\Repositories\DeliveryRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class DeliveryDomainService
{
    public function __construct(private readonly DeliveryRepositoryInterface $repository) {}

    public function distributorsQuery(): Builder
    {
        return $this->repository->distributorsQuery();
    }

    public function branchesQuery(): Builder
    {
        return $this->repository->branchesQuery();
    }

    public function locationLogsQuery(): Builder
    {
        return $this->repository->locationLogsQuery();
    }
}
