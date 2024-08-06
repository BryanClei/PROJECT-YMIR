<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class UnitFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [];
}
