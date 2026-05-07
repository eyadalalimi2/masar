<?php

namespace App\Jobs\Orders;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StoreDispatchAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $orderId,
        public readonly int $distributorId,
        public readonly string $actor,
        public readonly ?float $distanceKm,
        public readonly int $activeOrdersCount,
    ) {}

    public function handle(): void
    {
        Log::channel('operations')->info('Smart dispatch executed.', [
            'order_id' => $this->orderId,
            'distributor_id' => $this->distributorId,
            'actor' => $this->actor,
            'distance_km' => $this->distanceKm,
            'active_orders_count' => $this->activeOrdersCount,
        ]);
    }
}
