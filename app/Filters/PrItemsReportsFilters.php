<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class PrItemsReportsFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "id",
        "transaction_id",
        "reference_no",
        "item_id",
        "item_code",
        "item_name",
        "uom_id",
        "po_at",
        "purchase_order_id",
        "buyer_id",
        "buyer_name",
        "tagged_buyer",
        "quantity",
        // "item_stock",
        "remarks",
        "attachment",
        "assets",
        "warehouse_id",
        "category_id",
    ];

    protected array $relationSearch = [
        "transaction" => ["pr_year_number_id"],
        "po_transaction" => ["po_year_number_id"],
    ];

    // protected function processSearch($search)
    // {
    //     $this->builder
    //         ->leftJoin(
    //             "transaction",
    //             "pr_items.transaction_id",
    //             "=",
    //             "transaction.id"
    //         )
    //         ->leftJoin(
    //             "po_transaction",
    //             "pr_items.po_transaction_id",
    //             "=",
    //             "po_transaction.id"
    //         );

    //     $this->builder->where(function ($query) use ($search) {
    //         foreach ($this->columnSearch as $column) {
    //             $query->orWhere("pr_items." . $column, "like", "%{$search}%");
    //         }

    //         // Search in relationship columns
    //         foreach ($this->relationSearch as $table => $columns) {
    //             foreach ($columns as $column) {
    //                 $query->orWhere(
    //                     $table . "." . $column,
    //                     "like",
    //                     "%{$search}%"
    //                 );
    //             }
    //         }
    //     });
    // }

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
                ->when($status == "view_all", function ($q) {})
                ->when($status == "view_all_purchasing_monitoring", function (
                    $q
                ) {
                    $q->whereDoesntHave("po_transaction");
                });
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
