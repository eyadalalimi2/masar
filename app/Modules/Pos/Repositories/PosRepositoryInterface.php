<?php

namespace App\Modules\Pos\Repositories;

use Illuminate\Database\Eloquent\Builder;

interface PosRepositoryInterface
{
    public function posAccountsQuery(): Builder;

    public function salesQuery(): Builder;

    public function localProductsQuery(): Builder;
}
