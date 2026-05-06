<?php

namespace App\Modules\Workshop\Repositories;

use Illuminate\Database\Eloquent\Builder;

interface WorkshopRepositoryInterface
{
    public function servicesQuery(): Builder;

    public function serviceOrdersQuery(): Builder;
}
