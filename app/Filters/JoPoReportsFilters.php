<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class JoPoReportsFilters extends QueryFilters
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

    protected array $relationSearch = [
        "jo_po_transaction" => ["po_year_number_id"],
    ];

    protected function processSearch($search)
    {
        foreach ($this->relationSearch as $relation => $columns) {
            $this->builder->leftJoin(
                $relation,
                "po_orders.jo_po_id",
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
        $this->builder->whereHas("jo_po_transaction", function ($subQuery) use (
            $status
        ) {
            $subQuery
                ->when($status == "pending", function ($q) {
                    $q->whereIn("status", ["Pending", "For Approval"])
                        ->whereNull("approved_at")
                        ->whereNull("cancelled_at");
                })
                ->when($status == "approved", function ($q) {
                    $q->whereIn("status", ["Approved", "For Receiving"])
                        ->whereNotNull("approved_at")
                        ->whereNull("cancelled_at")
                        ->whereNull("rejected_at");
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
}
