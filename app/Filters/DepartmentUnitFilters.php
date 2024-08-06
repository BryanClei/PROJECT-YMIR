<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class DepartmentUnitFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = ["name", "code"];

    public function department_id($department_id)
    {
        $this->builder->where("department_id", $department_id);
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
