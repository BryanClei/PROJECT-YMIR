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

    /**
     * Apply custom search_approver logic, including columnSearch fields.
     */
    public function search_approver($search_approver)
    {
        $this->builder->where(function ($query) use ($search_approver) {
            // Search in related models
            $query
                ->whereHas("set_approver", function ($q) use (
                    $search_approver
                ) {
                    $q->where(
                        "approver_name",
                        "LIKE",
                        "%" . $search_approver . "%"
                    );
                })
                ->orWhereHas("business_unit", function ($q) use (
                    $search_approver
                ) {
                    $q->where("name", "LIKE", "%" . $search_approver . "%");
                })
                ->orWhereHas("sub_unit", function ($q) use ($search_approver) {
                    $q->where("name", "LIKE", "%" . $search_approver . "%");
                })
                ->orWhereHas("department", function ($q) use (
                    $search_approver
                ) {
                    $q->where("name", "LIKE", "%" . $search_approver . "%");
                })
                ->orWhereHas("locations", function ($q) use ($search_approver) {
                    $q->where("name", "LIKE", "%" . $search_approver . "%");
                });

            // Search in columns defined in $columnSearch
            foreach ($this->columnSearch as $column) {
                $query->orWhere($column, "LIKE", "%" . $search_approver . "%");
            }
        });
    }
}
