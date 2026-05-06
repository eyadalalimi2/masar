<?php

namespace App\Modules\Orders\Services;

use App\Modules\Orders\Repositories\OrdersRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class OrdersDomainService
{
    public function __construct(private readonly OrdersRepositoryInterface $repository) {}

    public function ordersQuery(): Builder
    {
        return $this->repository->ordersQuery();
    }

    public function orderItemsQuery(): Builder
    {
        return $this->repository->orderItemsQuery();
    }

    public function orderStatusHistoriesQuery(): Builder
    {
        return $this->repository->orderStatusHistoriesQuery();
    }
}
