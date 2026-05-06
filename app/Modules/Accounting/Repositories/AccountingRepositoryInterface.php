<?php

namespace App\Modules\Accounting\Repositories;

use Illuminate\Database\Eloquent\Builder;

interface AccountingRepositoryInterface
{
    public function customerAccountsQuery(): Builder;

    public function accountsQuery(): Builder;

    public function transactionsQuery(): Builder;

    public function paymentsQuery(): Builder;
}
