<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class PrItemsFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
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

    // protected array $relationSearch = [
    //     "transaction" => ["pr_year_number_id"],
    // ];

    // protected function processSearch($search)
    // {
    //     foreach ($this->relationSearch as $relation => $columns) {
    //         $this->builder->leftJoin(
    //             $relation,
    //             "pr_items.transaction_id",
    //             "=",
    //             $relation . ".id"
    //         );
    //     }

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
        $user_id = Auth()->user()->id;

        $this->builder
            ->when($status === "report_approved_user", function ($query) use (
                $user_id
            ) {
                $query->whereHas("transaction", function ($subQuery) use (
                    $user_id
                ) {
                    $subQuery
                        ->where("user_id", $user_id)
                        ->where("status", "Approved")
                        ->whereNotNull("approved_at")
                        ->whereNull("rejected_at")
                        ->whereNull("cancelled_at");
                });
            })
            ->when($status === "report_approved", function ($query) {
                $query->whereHas("transaction", function ($subQuery) {
                    $subQuery
                        ->where("status", "Approved")
                        ->whereNotNull("approved_at")
                        ->whereNull("rejected_at")
                        ->whereNull("cancelled_at");
                });
            })
            ->when($status === "admin_reports", function ($query) {
                $query->whereHas("transaction", function ($subQuery) {
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
