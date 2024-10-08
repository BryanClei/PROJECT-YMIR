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

    public function status($status)
    {
        $user_id = Auth()->user()->id;
        $this->builder
            ->when($status === "cancelled", function ($query) {
                $query->onlyTrashed();
            })
            ->when($status === "user_received", function ($query) use (
                $user_id
            ) {
                $query->where("received_by", $user_id);
            });
    }
}
