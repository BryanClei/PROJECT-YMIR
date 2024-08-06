<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class TypeFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [];
}
