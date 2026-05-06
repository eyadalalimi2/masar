<?php

namespace App\Modules\Users\Repositories;

use Illuminate\Database\Eloquent\Builder;

interface UsersRepositoryInterface
{
    public function usersQuery(): Builder;

    public function adminsQuery(): Builder;

    public function suppliersQuery(): Builder;

    public function customersQuery(): Builder;

    public function agentsQuery(): Builder;

    public function branchAccountsQuery(): Builder;

    public function distributorAccountsQuery(): Builder;
}
