<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class POItemsFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [];
}
