<?php

namespace App\Modules\Delivery\Repositories;

use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Builder;

interface SmartDispatchRepositoryInterface
{
    public function dispatchCandidatesQuery(int $supplierId, ?int $branchId = null): Builder;

    /**
     * @param array<int, int> $orderIds
     * @return array<int, int>
     */
    public function latestLocationIdsByOrderIds(array $orderIds): array;

    public function customerDestinationFromOrder(Order $order): ?array;
}
