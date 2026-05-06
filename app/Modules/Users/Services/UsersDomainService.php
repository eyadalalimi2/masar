<?php

namespace App\Modules\Users\Services;

use App\Modules\Users\Repositories\UsersRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class UsersDomainService
{
    public function __construct(private readonly UsersRepositoryInterface $repository) {}

    public function usersQuery(): Builder
    {
        return $this->repository->usersQuery();
    }

    public function adminsQuery(): Builder
    {
        return $this->repository->adminsQuery();
    }

    public function suppliersQuery(): Builder
    {
        $repository = $this->repository;

        return $repository->{'suppliersQuery'}();
    }

    public function customersQuery(): Builder
    {
        return $this->repository->customersQuery();
    }

    public function agentsQuery(): Builder
    {
        return $this->repository->agentsQuery();
    }

    public function branchAccountsQuery(): Builder
    {
        return $this->repository->branchAccountsQuery();
    }

    public function distributorAccountsQuery(): Builder
    {
        return $this->repository->distributorAccountsQuery();
    }
}
