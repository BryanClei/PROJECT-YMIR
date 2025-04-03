<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class POItemsFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "po_id",
        "reference_no",
        "pr_id",
        "pr_item_id",
        "item_id",
        "item_code",
        "item_name",
        "supplier_id",
        "uom_id",
        "price",
        "item_stock",
        "quantity",
        "quantity_serve",
        "total_price",
        "attachment",
        "buyer_id",
        "buyer_name",
        "remarks",
        "warehouse_id",
        "category_id",
    ];

    public function status($status)
    {
        $user_id = Auth()->user()->id;

        $this->builder
            ->when($status === "report_approved_user", function ($query) use (
                $user_id
            ) {
                $query->whereHas("po_transaction", function ($subQuery) use (
                    $user_id
                ) {
                    $subQuery
                        ->where("user_id", $user_id)
                        ->where("status", "For Receiving")
                        ->whereNotNull("approved_at")
                        ->whereNull("rejected_at")
                        ->whereNull("cancelled_at");
                });
            })
            ->when($status === "report_approved", function ($query) {
                $query->whereHas("po_transaction", function ($subQuery) {
                    $subQuery
                        ->where("status", "For Receiving")
                        ->whereNotNull("approved_at")
                        ->whereNull("rejected_at")
                        ->whereNull("cancelled_at");
                });
            })
            ->when($status === "admin_reports", function ($query) {
                $query->whereHas("po_transaction", function ($subQuery) {
                    $subQuery
                        ->where(function ($query) {
                            $query
                                ->where("status", "Pending")
                                ->orWhere("status", "For Approval");
                        })
                        ->whereNull("approved_at")
                        ->whereNull("rejected_at")
                        ->whereNull("cancelled_at");
                });
            });
    }

    public function from($from)
    {
        $this->builder->whereHas("po_transaction", function ($query) use (
            $from
        ) {
            $query->whereDate("created_at", ">=", $from);
        });
    }
    public function to($to)
    {
        $this->builder->whereHas("po_transaction", function ($query) use ($to) {
            $query->whereDate("created_at", "<=", $to);
        });
    }

    public function buyer($buyer)
    {
        $this->builder->when($buyer, function ($query) use ($buyer) {
            $query->where("buyer_id", $buyer);
        });
    }
}
