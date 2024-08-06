<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class AccountTitleUnitsFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = ["name"];
}
