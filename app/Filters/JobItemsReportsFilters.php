<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class JobItemsReportsFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "jo_transaction_id",
        "description",
        "uom_id",
        "po_at",
        "purchase_order_id",
        "quantity",
        "unit_price",
        "total_price",
        "remarks",
        "attachment",
        "asset",
        "asset_code",
        "helpdesk_id",
        "reference_no",
    ];

    protected array $relationSearch = [
        "transaction" => ["jo_year_number_id"],
    ];

    protected function processSearch($search)
    {
        foreach ($this->relationSearch as $relation => $columns) {
            $this->builder->leftJoin(
                $relation,
                "jo_items.jo_transaction_id",
                "=",
                $relation . ".id"
            );
        }

        $this->builder->where(function ($query) use ($search) {
            foreach ($this->columnSearch as $column) {
                $query->orWhere("jo_items." . $column, "like", "%{$search}%");
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
        $this->builder->whereHas("transaction", function ($subQuery) use (
            $status
        ) {
            $subQuery
                ->when($status == "pending", function ($q) {
                    $q->whereIn("status", ["Pending", "For Approval"])
                        ->whereNull("approved_at")
                        ->whereNull("cancelled_at");
                })
                ->when($status == "approved", function ($q) {
                    $q->where("status", "Approved")->whereNotNull(
                        "approved_at"
                    );
                })
                ->when($status == "cancelled", function ($q) {
                    $q->where("status", "Cancelled")->whereNotNull(
                        "cancelled_at"
                    );
                })
                ->when($status == "rejected", function ($q) {
                    $q->where("status", "Reject");
                })
                ->when($status == "returned", function ($q) {
                    $q->where("status", "Return");
                })
                ->when($status == "admin_reports", function ($q) {
                    $q->whereNot("status", "Cancelled");
                })
                ->when($status == "view_all", function ($q) {});
        });
    }

    public function from($from)
    {
        $this->builder->whereHas("transaction", function ($query) use ($from) {
            $query->whereDate("created_at", ">=", $from);
        });
    }
    public function to($to)
    {
        $this->builder->whereHas("transaction", function ($query) use ($to) {
            $query->whereDate("created_at", "<=", $to);
        });
    }
}
