<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class UserFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "prefix_id",
        "id_number",
        "first_name",
        "middle_name",
        "last_name",
        "suffix",
        "position_name",
        "company_id",
        "business_unit_id",
        "department_id",
        "department_unit_id",
        "sub_unit_id",
        "location_id",
        "warehouse_id",
    ];

    public function approver($approver)
    {
        $this->builder->when($approver == "active", function ($query) {
            $query->whereHas("role", function ($query) {
                $query->where("name", "Approver");
            });
        });
    }

    public function access_permission($access_permission)
    {
        return $this->builder->whereHas("role", function ($query) use (
            $access_permission
        ) {
            $query->whereRaw("FIND_IN_SET(?, access_permission)", [
                $access_permission,
            ]);
        });
    }

    public function buyer($buyer)
    {
        $this->builder->when($buyer == "active", function ($query) {
            $query->whereHas("role", function ($query) {
                $query->where("name", "Buyer");
            });
        });
    }
}
