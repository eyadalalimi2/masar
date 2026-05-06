<?php

namespace App\Modules\Accounting\Repositories;

use App\Models\Finance\Account;
use App\Models\Finance\CustomerAccount;
use App\Models\Finance\Payment;
use App\Models\Finance\Transaction;
use Illuminate\Database\Eloquent\Builder;

class EloquentAccountingRepository implements AccountingRepositoryInterface
{
    public function customerAccountsQuery(): Builder
    {
        return CustomerAccount::query();
    }

    public function accountsQuery(): Builder
    {
        return Account::query();
    }

    public function transactionsQuery(): Builder
    {
        return Transaction::query();
    }

    public function paymentsQuery(): Builder
    {
        return Payment::query();
    }
}
