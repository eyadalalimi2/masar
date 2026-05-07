<?php

namespace App\Events\Orders;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DistributorAutoAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $orderId,
        public readonly int $distributorId,
        public readonly string $actor,
        public readonly ?float $distanceKm,
        public readonly int $activeOrdersCount,
    ) {}
}
