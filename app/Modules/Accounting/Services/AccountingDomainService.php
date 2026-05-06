<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Repositories\AccountingRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class AccountingDomainService
{
    public function __construct(private readonly AccountingRepositoryInterface $repository) {}

    public function customerAccountsQuery(): Builder
    {
        $repository = $this->repository;

        return $repository->{'customerAccountsQuery'}();
    }

    public function accountsQuery(): Builder
    {
        return $this->repository->accountsQuery();
    }

    public function transactionsQuery(): Builder
    {
        return $this->repository->transactionsQuery();
    }

    public function paymentsQuery(): Builder
    {
        return $this->repository->paymentsQuery();
    }
}
