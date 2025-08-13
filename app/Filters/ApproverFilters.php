<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class ApproverFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = ["name"];

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
            })
            ->orWhereHas("sub_unit", function ($query) use ($search_approver) {
                $query->where("name", "LIKE", "%" . $search_approver . "%");
            })
            ->orWhereHas("department", function ($query) use (
                $search_approver
            ) {
                $query->where("name", "LIKE", "%" . $search_approver . "%");
            })
            ->orWhereHas("locations", function ($query) use ($search_approver) {
                $query->where("name", "LIKE", "%" . $search_approver . "%");
            });
    }
}
