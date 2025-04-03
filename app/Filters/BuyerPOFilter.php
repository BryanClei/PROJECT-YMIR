<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class BuyerPOFilter extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "po_year_number_id",
        "pr_number",
        "po_number",
        "po_description",
        "date_needed",
        "user_id",
        "type_id",
        "type_name",
        "business_unit_id",
        "business_unit_name",
        "company_id",
        "company_name",
        "department_id",
        "department_name",
        "department_unit_id",
        "department_unit_name",
        "location_id",
        "location_name",
        "sub_unit_id",
        "sub_unit_name",
        "account_title_id",
        "account_title_name",
        "supplier_id",
        "supplier_name",
        "module_name",
    ];

    protected array $relationSearch = [
        "pr_transaction" => ["pr_year_number_id"],
    ];

    protected function processSearch($search)
    {
        // Join the required relationships first
        foreach ($this->relationSearch as $relation => $columns) {
            $this->builder->leftJoin(
                $relation,
                "po_transactions.pr_number",
                "=",
                $relation . ".pr_number"
            );
        }

        $this->builder->where(function ($query) use ($search) {
            // Search in main table columns
            foreach ($this->columnSearch as $column) {
                $query->orWhere(
                    "po_transactions." . $column,
                    "like",
                    "%{$search}%"
                );
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

    public function search_business_unit($search_business_unit)
    {
        $this->builder
            ->where(
                "business_unit_name",
                "like",
                "%" . $search_business_unit . "%"
            )
            ->orWhere(
                "business_unit_id",
                "like",
                "%" . $search_business_unit . "%"
            );
    }

    public function status($status)
    {
        $user_id = auth()->user()->id;

        $this->builder
            ->when($status === "pending", function ($query) use ($user_id) {
                $query
                    ->whereNull("approved_at")
                    ->whereNull("rejected_at")
                    ->whereNull("deleted_at")
                    ->where(function ($subQuery) {
                        $subQuery
                            ->where("status", "Pending")
                            ->orWhere("status", "For Approval");
                    })
                    ->whereHas("order", function ($orderQuery) use ($user_id) {
                        $orderQuery->where("buyer_id", $user_id);
                    });
            })
            ->when($status === "po_approved", function ($query) use ($user_id) {
                $query
                    ->whereHas("order", function ($orderQuery) use ($user_id) {
                        $orderQuery->where("buyer_id", $user_id);
                    })
                    ->where("status", "For Receiving")
                    ->whereNotNull("approved_at")
                    ->whereNull("cancelled_at")
                    ->whereNull("rejected_at")
                    ->whereHas("approver_history", function ($query) {
                        $query->whereNotNull("approved_at");
                    });
            })
            ->when($status === "rejected", function ($query) use ($user_id) {
                $query
                    ->where("status", "Reject")
                    ->whereHas("order", function ($subQuery) use ($user_id) {
                        $subQuery->where("buyer_id", $user_id);
                    });
            })
            ->when($status === "cancelled", function ($query) use ($user_id) {
                $query
                    ->whereHas("order", function ($query) use ($user_id) {
                        $query->where("buyer_id", $user_id)->withTrashed();
                    })
                    ->where("status", "Cancelled")
                    ->whereNotNull("cancelled_at");
            })
            ->when($status === "s_buyer", function ($query) {
                $query;
            })
            ->when($status === "pending_to_receive", function ($query) use (
                $user_id
            ) {
                $query
                    ->where("status", "For Receiving")
                    ->whereHas("order", function ($subQuery) use ($user_id) {
                        $subQuery
                            ->where("buyer_id", $user_id)
                            ->where("quantity_serve", ">", 0)
                            ->whereColumn("quantity_serve", "<", "quantity");
                    });
            })
            ->when($status === "completed", function ($query) {
                $query
                    ->where("status", "For Receiving")
                    ->whereHas("order", function ($subQuery) {
                        $subQuery->whereColumn(
                            "quantity_serve",
                            ">=",
                            "quantity"
                        );
                    })
                    ->whereHas("rr_transaction");
            });
    }
}
