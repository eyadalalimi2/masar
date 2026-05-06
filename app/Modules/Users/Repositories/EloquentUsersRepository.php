<?php

namespace App\Modules\Users\Repositories;

use App\Models\Admin;
use App\Models\Customer\Customer;
use App\Models\Distribution\BranchAccount;
use App\Models\Distribution\DistributorAccount;
use App\Models\Supplier\Agent;
use App\Models\Supplier\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class EloquentUsersRepository implements UsersRepositoryInterface
{
    public function usersQuery(): Builder
    {
        return User::query();
    }

    public function adminsQuery(): Builder
    {
        return Admin::query();
    }

    public function suppliersQuery(): Builder
    {
        return Supplier::query();
    }

    public function customersQuery(): Builder
    {
        return Customer::query();
    }

    public function agentsQuery(): Builder
    {
        return Agent::query();
    }

    public function branchAccountsQuery(): Builder
    {
        return BranchAccount::query();
    }

    public function distributorAccountsQuery(): Builder
    {
        return DistributorAccount::query();
    }
}
