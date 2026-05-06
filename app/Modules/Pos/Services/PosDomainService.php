<?php

namespace App\Modules\Pos\Services;

use App\Modules\Pos\Repositories\PosRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class PosDomainService
{
    public function __construct(private readonly PosRepositoryInterface $repository) {}

    public function posAccountsQuery(): Builder
    {
        return $this->repository->posAccountsQuery();
    }

    public function salesQuery(): Builder
    {
        return $this->repository->salesQuery();
    }

    public function localProductsQuery(): Builder
    {
        return $this->repository->localProductsQuery();
    }
}
