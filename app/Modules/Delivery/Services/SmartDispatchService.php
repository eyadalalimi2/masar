<?php

namespace App\Modules\Delivery\Services;

use App\Events\Orders\DistributorAutoAssigned;
use App\Models\Orders\Order;
use App\Modules\Delivery\DTOs\DispatchSelectionData;
use App\Modules\Delivery\Repositories\SmartDispatchRepositoryInterface;
use App\Services\Orders\OrderService;

class SmartDispatchService
{
    public function __construct(
        private readonly SmartDispatchRepositoryInterface $repository,
        private readonly OrderService $orderService,
    ) {}

    public function autoAssignForAdmin(Order $order): ?DispatchSelectionData
    {
        $selection = $this->chooseDistributor(
            order: $order,
            supplierId: (int) $order->supplier_id,
            branchId: $order->branch_id ? (int) $order->branch_id : null,
            strategy: 'load-balanced'
        );

        return $this->assignAndDispatchEvent($order, $selection, 'admin');
    }

    public function autoAssignForAgent(Order $order, int $supplierId): ?DispatchSelectionData
    {
        $selection = $this->chooseDistributor(
            order: $order,
            supplierId: $supplierId,
            branchId: null,
            strategy: 'load-balanced'
        );

        return $this->assignAndDispatchEvent($order, $selection, 'agent');
    }

    public function autoAssignForBranch(Order $order, int $branchId): ?DispatchSelectionData
    {
        $selection = $this->chooseDistributor(
            order: $order,
            supplierId: (int) $order->supplier_id,
            branchId: $branchId,
            strategy: 'branch-balanced'
        );

        return $this->assignAndDispatchEvent($order, $selection, 'branch');
    }

    /**
     * @param array<int, int> $orderIds
     */
    public function latestLocationIdsByOrderIds(array $orderIds): array
    {
        return $this->repository->latestLocationIdsByOrderIds($orderIds);
    }

    private function chooseDistributor(Order $order, int $supplierId, ?int $branchId, string $strategy): ?DispatchSelectionData
    {
        if ($supplierId <= 0) {
            return null;
        }

        $destination = $this->repository->customerDestinationFromOrder($order);
        $query = $this->repository->dispatchCandidatesQuery($supplierId, $branchId)
            ->withCount([
                'orders as active_orders_count' => function ($query) {
                    $query->whereIn('status', [
                        Order::STATUS_ASSIGNED,
                        Order::STATUS_OUT_FOR_DELIVERY,
                    ]);
                },
            ]);

        if ($strategy === 'branch-balanced') {
            $query->withCount([
                'orders as delayed_orders_count' => function ($query) {
                    $query->whereIn('status', [
                        Order::STATUS_ASSIGNED,
                        Order::STATUS_OUT_FOR_DELIVERY,
                    ])->where('updated_at', '<=', now()->subHours(2));
                },
                'orders as delivered_today_count' => function ($query) {
                    $query->where('status', Order::STATUS_DELIVERED)
                        ->whereDate('updated_at', now()->toDateString());
                },
            ]);
        }

        if ($destination !== null) {
            [$lat, $lng] = $destination;
            $query->selectRaw('ST_Distance_Sphere(dl.location, POINT(?, ?)) / 1000 as distance_km', [$lng, $lat])
                ->orderByRaw('COALESCE(distance_km, 999999)');
        }

        if ($strategy === 'branch-balanced') {
            $query->orderByRaw('((active_orders_count * 3) + (delayed_orders_count * 2) - (delivered_today_count * 0.2)) asc');
        } else {
            $query->orderBy('active_orders_count');
        }

        $candidate = $query->orderBy('id')->first();
        if (! $candidate) {
            return null;
        }

        return new DispatchSelectionData(
            distributorId: (int) $candidate->id,
            distributorName: (string) $candidate->name,
            activeOrdersCount: (int) ($candidate->active_orders_count ?? 0),
            distanceKm: isset($candidate->distance_km) ? (float) $candidate->distance_km : null,
        );
    }

    private function assignAndDispatchEvent(Order $order, ?DispatchSelectionData $selection, string $actor): ?DispatchSelectionData
    {
        if (! $selection) {
            return null;
        }

        $this->orderService->assignDistributor($order, $selection->distributorId);

        DistributorAutoAssigned::dispatch(
            orderId: (int) $order->id,
            distributorId: $selection->distributorId,
            actor: $actor,
            distanceKm: $selection->distanceKm,
            activeOrdersCount: $selection->activeOrdersCount,
        );

        return $selection;
    }
}
