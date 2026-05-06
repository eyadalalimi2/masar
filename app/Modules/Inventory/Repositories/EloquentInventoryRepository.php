<?php

namespace App\Modules\Inventory\Repositories;

use App\Models\Catalog\InventoryMovement;
use App\Models\Catalog\Product;
use App\Models\Distribution\BranchProductStock;
use Illuminate\Database\Eloquent\Builder;

class EloquentInventoryRepository implements InventoryRepositoryInterface
{
    public function productsQuery(): Builder
    {
        return Product::query();
    }

    public function stocksQuery(): Builder
    {
        return BranchProductStock::query();
    }

    public function movementsQuery(): Builder
    {
        return InventoryMovement::query();
    }
}
