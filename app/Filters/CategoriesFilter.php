<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class CategoriesFilter extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = ["name", "code"];
}
