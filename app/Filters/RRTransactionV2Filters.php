<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class RRTransactionV2Filters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "rr_year_number_id",
        "po_id",
        "pr_id",
        "received_by",
        "tagging_id",
    ];

    public function status($status)
    {
        $user_id = Auth()->user()->id;
        $this->builder
            ->when($status === "view_all", function ($query) {
                $query->whereHas("po_transaction", function ($subQuery) {
                    $subQuery->where("module_name", "Asset");
                });
            })
            ->when($status === "cancelled", function ($query) {
                $query->onlyTrashed()->with([
                    "rr_orders" => function ($subQuery) {
                        $subQuery->onlyTrashed();
                    },
                ]);
            })
            ->when($status === "user_receiving", function ($query) use (
                $user_id
            ) {
                $query->where("received_by", $user_id);
            })
            ->when($status === "buyer_completed", function ($query) use (
                $user_id
            ) {
                $query->whereHas("rr_orders.order", function ($subQuery) use (
                    $user_id
                ) {
                    $subQuery->where("buyer_id", $user_id);
                });
            });
    }
}
