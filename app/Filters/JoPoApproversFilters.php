<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class JoPoApproversFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "module",
        "company_id",
        "company_name",
        "business_unit_id",
        "business_unit_name",
        "department_id",
        "department_name",
    ];

    public function search_approver($search_approver)
    {
        $this->builder->whereHas("set_approver", function ($query) use (
            $search_approver
        ) {
            $query->where(
                "approver_name",
                "LIKE",
                "%" . $search_approver . "%"
            );
        });

        $this->builder
            ->orWhere("module", "LIKE", "%" . $search_approver . "%")
            ->orWhere("company_id", "LIKE", "%" . $search_approver . "%")
            ->orWhere("company_name", "LIKE", "%" . $search_approver . "%")
            ->orWhere("business_unit_id", "LIKE", "%" . $search_approver . "%")
            ->orWhere(
                "business_unit_name",
                "LIKE",
                "%" . $search_approver . "%"
            )
            ->orWhere("department_id", "LIKE", "%" . $search_approver . "%")
            ->orWhere("department_name", "LIKE", "%" . $search_approver . "%");
    }
}
