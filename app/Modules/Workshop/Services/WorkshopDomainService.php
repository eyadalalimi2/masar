<?php

namespace App\Modules\Workshop\Services;

use App\Modules\Workshop\Repositories\WorkshopRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class WorkshopDomainService
{
    public function __construct(private readonly WorkshopRepositoryInterface $repository) {}

    public function servicesQuery(): Builder
    {
        return $this->repository->servicesQuery();
    }

    public function serviceOrdersQuery(): Builder
    {
        return $this->repository->serviceOrdersQuery();
    }
}
