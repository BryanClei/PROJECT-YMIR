<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class JORRTransactionFilter extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [];

    public function status($status)
    {
        $this->builder
            ->when($status === "cancel", function ($query) {
                $query->whereNotNull("deleted_at");
            })
            ->when($status === "received", function ($query) {
                $query->whereNull("deleted_at");
            });
    }
}
