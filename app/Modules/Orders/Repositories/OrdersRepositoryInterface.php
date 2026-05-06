<?php

namespace App\Modules\Orders\Repositories;

use Illuminate\Database\Eloquent\Builder;

interface OrdersRepositoryInterface
{
    public function ordersQuery(): Builder;

    public function orderItemsQuery(): Builder;

    public function orderStatusHistoriesQuery(): Builder;
}
