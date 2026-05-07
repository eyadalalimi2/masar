<?php

namespace App\Modules\Delivery\DTOs;

final class DispatchSelectionData
{
    public function __construct(
        public readonly int $distributorId,
        public readonly string $distributorName,
        public readonly int $activeOrdersCount,
        public readonly ?float $distanceKm,
    ) {}
}
