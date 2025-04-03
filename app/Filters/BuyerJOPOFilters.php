<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class BuyerJOPOFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [];

    public function status($status)
    {
        $user_id = auth()->user()->id;

        $this->builder
            ->when($status === "pending", function ($query) use ($user_id) {
                $query
                    ->whereHas("jo_po_orders", function ($query) use (
                        $user_id
                    ) {
                        $query->where("buyer_id", $user_id);
                    })
                    ->whereIn("status", ["Pending", "For Approval"])
                    ->whereNull("approved_at")
                    ->whereNull("rejected_at")
                    ->whereNull("cancelled_at");
            })
            ->when($status === "approved", function ($query) use ($user_id) {
                $query
                    ->whereHas("jo_po_orders", function ($query) use (
                        $user_id
                    ) {
                        $query->where("buyer_id", $user_id);
                    })
                    ->where("status", "For Receiving")
                    ->whereNotNull("approved_at");
            })
            ->when($status === "rejected", function ($query) use ($user_id) {
                $query
                    ->whereHas("jo_po_orders", function ($query) use (
                        $user_id
                    ) {
                        $query->where("buyer_id", $user_id);
                    })
                    ->where("status", "Rejected")
                    ->whereNotNull("cancelled_at")
                    ->whereNotNull("rejected_at");
            })
            ->when($status === "cancelled", function ($query) use ($user_id) {
                $query
                    ->whereHas("jo_po_orders", function ($query) use (
                        $user_id
                    ) {
                        $query->where("buyer_id", $user_id);
                    })
                    ->where("status", "Cancelled")
                    ->whereNotNull("rejected_at")
                    ->whereNotNull("cancelled_at");
            });
    }
}
