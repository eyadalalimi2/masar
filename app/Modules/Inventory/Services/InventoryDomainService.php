<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Repositories\InventoryRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class InventoryDomainService
{
    public function __construct(private readonly InventoryRepositoryInterface $repository) {}

    public function productsQuery(): Builder
    {
        return $this->repository->productsQuery();
    }

    public function stocksQuery(): Builder
    {
        return $this->repository->stocksQuery();
    }

    public function movementsQuery(): Builder
    {
        return $this->repository->movementsQuery();
    }
}
