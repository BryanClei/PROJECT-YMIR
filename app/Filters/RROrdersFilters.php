<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class RROrdersFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [];

    public function from($from)
    {
        $this->builder->whereDate("rr_date", ">=", $from);
    }
    public function to($to)
    {
        $this->builder->whereDate("rr_date", "<=", $to);
    }
}
