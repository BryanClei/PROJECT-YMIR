<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class ShippingFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = ["location", "address"];

    public function vladimir($vladimir)
    {
        $this->builder->when($vladimir == "sync", function ($query) use (
            $vladimir
        ) {
            $query->withTrashed();
        });
    }
}
