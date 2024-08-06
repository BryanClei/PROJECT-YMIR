<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class DepartmentFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = ["name", "code"];

    public function business_unit_id($business_unit_id)
    {
        $this->builder->where("business_unit_id", $business_unit_id);
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
