<?php

namespace App\Modules\Orders\Repositories;

use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Orders\OrderStatusHistory;
use Illuminate\Database\Eloquent\Builder;

class EloquentOrdersRepository implements OrdersRepositoryInterface
{
    public function ordersQuery(): Builder
    {
        return Order::query();
    }

    public function orderItemsQuery(): Builder
    {
        return OrderItem::query();
    }

    public function orderStatusHistoriesQuery(): Builder
    {
        return OrderStatusHistory::query();
    }
}
