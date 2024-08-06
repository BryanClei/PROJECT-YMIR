<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class BusinessUnitFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = ["name", "code"];

    public function company_id($company_id)
    {
        $this->builder->where("company_id", $company_id);
    }

    public function vladimir($vladimir)
    {
        $this->builder->when($vladimir == "sync", function ($query) use (
            $vladimir
        ) {
            $query->withTrashed();
        });
    }
}
