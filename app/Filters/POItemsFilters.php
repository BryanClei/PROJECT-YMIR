<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class POItemsFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    public function from($from)
    {
        $this->builder->whereHas("po_transaction", function ($query) use (
            $from
        ) {
            $query->whereDate("date_needed", ">=", $from);
        });
    }
    public function to($to)
    {
        $this->builder->whereHas("po_transaction", function ($query) use ($to) {
            $query->whereDate("date_needed", "<=", $to);
        });
    }

    protected array $columnSearch = [];
}
