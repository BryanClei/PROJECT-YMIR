<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class PoApproversFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [];

    public function search_approver($search_approver)
    {
        $this->builder->where(function ($query) use ($search_approver) {
            $query
                ->whereHas("set_approver", function ($query) use (
                    $search_approver
                ) {
                    $query->where(
                        "approver_name",
                        "LIKE",
                        "%" . $search_approver . "%"
                    );
                })
                ->orWhere("module", "LIKE", "%" . $search_approver . "%")
                ->orWhere("company_name", "LIKE", "%" . $search_approver . "%")
                ->orWhere(
                    "business_unit_id",
                    "LIKE",
                    "%" . $search_approver . "%"
                )
                ->orWhere(
                    "business_unit_name",
                    "LIKE",
                    "%" . $search_approver . "%"
                )
                ->orWhere("department_id", "LIKE", "%" . $search_approver . "%")
                ->orWhere(
                    "department_name",
                    "LIKE",
                    "%" . $search_approver . "%"
                );
        });
    }
}
