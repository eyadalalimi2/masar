<?php

namespace App\Listeners\Orders;

use App\Events\Orders\DistributorAutoAssigned;
use App\Jobs\Orders\StoreDispatchAnalyticsJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class QueueDispatchAnalyticsListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(DistributorAutoAssigned $event): void
    {
        StoreDispatchAnalyticsJob::dispatch(
            orderId: $event->orderId,
            distributorId: $event->distributorId,
            actor: $event->actor,
            distanceKm: $event->distanceKm,
            activeOrdersCount: $event->activeOrdersCount,
        );
    }
}
