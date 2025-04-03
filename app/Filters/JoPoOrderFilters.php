<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class JoPoOrderFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "jo_transaction_id",
        "jo_item_id",
        "jo_po_id",
        "description",
        "uom_id",
        "quantity",
        "quantity_serve",
        "unit_price",
        "total_price",
        "remarks",
        "attachment",
        "asset",
        "asset_code",
        "helpdesk_id",
        "buyer_id",
        "buyer_name",
    ];

    public function status($status)
    {
        $user_id = Auth()->user()->id;
        $this->builder->when($status === "admin_reports", function ($query) {
            $query->whereHas("jo_po_transaction", function ($subQuery) {
                $subQuery
                    ->where("status", "Pending")
                    ->orWhere("status", "For Approval")
                    ->whereNull("approved_at")
                    ->whereNull("rejected_at")
                    ->whereNull("cancelled_at");
            });
        });
    }
}
