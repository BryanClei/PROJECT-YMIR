<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class RRTransactionFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "po_id",
        "pr_id",
        "received_by",
        "tagging_id",
    ];
}
