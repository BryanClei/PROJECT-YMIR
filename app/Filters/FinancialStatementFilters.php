<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class FinancialStatementFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [];
}
