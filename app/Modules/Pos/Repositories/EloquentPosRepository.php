<?php

namespace App\Modules\Pos\Repositories;

use App\Models\Pos;
use App\Models\PosLocalProduct;
use App\Models\PosSale;
use Illuminate\Database\Eloquent\Builder;

class EloquentPosRepository implements PosRepositoryInterface
{
    public function posAccountsQuery(): Builder
    {
        return Pos::query();
    }

    public function salesQuery(): Builder
    {
        return PosSale::query();
    }

    public function localProductsQuery(): Builder
    {
        return PosLocalProduct::query();
    }
}
