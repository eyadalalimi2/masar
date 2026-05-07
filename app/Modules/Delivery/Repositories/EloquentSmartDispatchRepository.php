<?php

namespace App\Modules\Delivery\Repositories;

use App\Models\Distribution\Distributor;
use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EloquentSmartDispatchRepository implements SmartDispatchRepositoryInterface
{
    public function dispatchCandidatesQuery(int $supplierId, ?int $branchId = null): Builder
    {
        return Distributor::query()
            ->where('supplier_id', $supplierId)
            ->where('status', 'active')
            ->when($branchId !== null, fn($query) => $query->where('branch_id', $branchId))
            ->leftJoinSub(
                DB::table('distributor_location_logs as dll')
                    ->selectRaw('dll.distributor_id, MAX(dll.id) as latest_log_id')
                    ->groupBy('dll.distributor_id'),
                'latest_logs',
                fn($join) => $join->on('latest_logs.distributor_id', '=', 'distributors.id')
            )
            ->leftJoin('distributor_location_logs as dl', 'dl.id', '=', 'latest_logs.latest_log_id')
            ->select('distributors.*');
    }

    public function latestLocationIdsByOrderIds(array $orderIds): array
    {
        if ($orderIds === []) {
            return [];
        }

        return DB::table('distributor_location_logs')
            ->whereIn('order_id', $orderIds)
            ->selectRaw('MAX(id) as id')
            ->groupBy('order_id')
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();
    }

    public function customerDestinationFromOrder(Order $order): ?array
    {
        $value = $order->customer_address;
        if (! is_string($value) || ! str_contains($value, ',')) {
            return null;
        }

        [$lat, $lng] = array_map('trim', explode(',', $value, 2));
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return [(float) $lat, (float) $lng];
    }
}
