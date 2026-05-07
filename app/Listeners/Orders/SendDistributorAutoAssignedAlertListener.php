<?php

namespace App\Listeners\Orders;

use App\Events\Orders\DistributorAutoAssigned;
use App\Models\Distribution\Distributor;
use App\Models\Orders\Order;
use App\Services\Notifications\WebAlertService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendDistributorAutoAssignedAlertListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private readonly WebAlertService $webAlertService) {}

    public function handle(DistributorAutoAssigned $event): void
    {
        $distributor = Distributor::query()->with(['account'])->find($event->distributorId);
        $order = Order::query()->with(['branch.account'])->find($event->orderId);

        if (! $order || ! $distributor) {
            return;
        }

        $distanceText = $event->distanceKm !== null
            ? ' (المسافة التقريبية: ' . number_format($event->distanceKm, 2) . ' كم)'
            : '';

        if ($distributor->account) {
            $this->webAlertService->create(
                'distributor_account',
                (int) $distributor->account->id,
                'تم إسناد طلب تلقائيًا',
                'تم إسناد الطلب #' . $order->id . ' لك عبر التوزيع الذكي' . $distanceText,
                [
                    'type' => 'smart_dispatch_assigned',
                    'order_id' => (int) $order->id,
                    'distributor_id' => (int) $distributor->id,
                    'actor' => $event->actor,
                ]
            );
        }

        $branchAccountId = (int) ($order->branch?->account?->id ?? 0);
        if ($branchAccountId > 0) {
            $this->webAlertService->create(
                'branch_account',
                $branchAccountId,
                'تم توزيع الطلب ذكيًا',
                'تم تعيين المندوب ' . $distributor->name . ' على الطلب #' . $order->id,
                [
                    'type' => 'smart_dispatch_notice',
                    'order_id' => (int) $order->id,
                    'distributor_id' => (int) $distributor->id,
                    'actor' => $event->actor,
                ]
            );
        }
    }
}
