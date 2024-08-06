<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class SubUnitFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = ["name", "code"];

    public function department_unit_id($department_unit_id)
    {
        $this->builder->where("department_unit_id", $department_unit_id);
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
