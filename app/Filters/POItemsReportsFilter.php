<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class POItemsReportsFilter extends QueryFilters
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

    protected array $relationSearch = [
        "po_transaction" => ["po_year_number_id"],
    ];

    protected function processSearch($search)
    {
        foreach ($this->relationSearch as $relation => $columns) {
            $this->builder->leftJoin(
                $relation,
                "po_orders.po_id",
                "=",
                $relation . ".id"
            );
        }

        $this->builder->where(function ($query) use ($search) {
            foreach ($this->columnSearch as $column) {
                $query->orWhere("po_orders." . $column, "like", "%{$search}%");
            }

            // Search in relationship columns
            foreach ($this->relationSearch as $table => $columns) {
                foreach ($columns as $column) {
                    $query->orWhere(
                        $table . "." . $column,
                        "like",
                        "%{$search}%"
                    );
                }
            }
        });
    }

    public function status($status)
    {
        $this->builder->whereHas("po_transaction", function ($subQuery) use (
            $status
        ) {
            $subQuery
                ->withTrashed()
                ->when($status == "pending", function ($q) {
                    $q->whereIn("status", ["Pending", "For Approval"])
                        ->whereNull("approved_at")
                        ->whereNull("cancelled_at");
                })
                ->when($status == "approved", function ($q) {
                    $q->whereIn("status", [
                        "Approved",
                        "For Receiving",
                    ])->whereNotNull("approved_at");
                })
                ->when($status == "cancelled", function ($q) {
                    $q->where("status", "Cancelled")->whereNotNull(
                        "cancelled_at"
                    );
                })
                ->when($status == "rejected", function ($q) {
                    $q->where("status", "Rejected");
                })
                ->when($status == "admin_reports", function ($q) {
                    $q->whereNot("status", "Cancelled");
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
