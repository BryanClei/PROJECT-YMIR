<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class SuppliersFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = ["type", "name"];

    public function vladimir($vladimir)
    {
        $this->builder->when($vladimir == "sync", function ($query) use (
            $vladimir
        ) {
            $query->withTrashed();
        });
    }
}
