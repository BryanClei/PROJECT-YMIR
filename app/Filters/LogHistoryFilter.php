<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class LogHistoryFilter extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "activity",
        "pr_id",
        "po_id",
        "jo_id",
        "jo_po_id",
        "action_by",
    ];
}
