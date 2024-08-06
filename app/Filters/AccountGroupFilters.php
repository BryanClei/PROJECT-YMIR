<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class AccountGroupFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = ["name"];
}
