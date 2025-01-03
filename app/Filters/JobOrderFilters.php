<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class JobOrderFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "module",
        "company_id",
        "business_unit_id",
        "department_id",
        "department_unit_id",
        "sub_unit_id",
        "location_id",
    ];

    public function search_approver($search_approver)
    {
        $this->builder
            ->whereHas("set_approver", function ($query) use (
                $search_approver
            ) {
                $query->where(
                    "approver_name",
                    "LIKE",
                    "%" . $search_approver . "%"
                );
            })
            ->orWhereHas("business_unit", function ($query) use (
                $search_approver
            ) {
                $query->where("name", "LIKE", "%" . $search_approver . "%");
            });
    }
}
