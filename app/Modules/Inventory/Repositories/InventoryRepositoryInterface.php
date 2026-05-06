<?php

namespace App\Modules\Inventory\Repositories;

use Illuminate\Database\Eloquent\Builder;

interface InventoryRepositoryInterface
{
    public function productsQuery(): Builder;

    public function stocksQuery(): Builder;

    public function movementsQuery(): Builder;
}
