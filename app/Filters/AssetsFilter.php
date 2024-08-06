<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class AssetsFilter extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = ["name", "tag_number"];
}
