<?php

namespace App\Services\Catalog;

use App\Models\Catalog\ProductionYear;

class ProductionYearService
{
    public function create(array $data): ProductionYear
    {
        return ProductionYear::create($data);
    }

    public function update(ProductionYear $productionYear, array $data): ProductionYear
    {
        $productionYear->update($data);

        return $productionYear->fresh();
    }

    public function delete(ProductionYear $productionYear): void
    {
        $productionYear->delete();
    }
}






