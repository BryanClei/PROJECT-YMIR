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
}
