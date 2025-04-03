<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class PurchaseAssistantPOFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "po_year_number_id",
        "po_number",
        "pr_number",
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

    public function search_business_unit($search_business_unit, $status = null)
    {
        $this->builder->where(function ($query) use ($search_business_unit) {
            $query
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
        });

        // Add status filter if provided
        if ($status !== null) {
            $this->builder->where("status", $status);
        }

        return $this->builder;
    }

    public function status($status)
    {
        $this->builder
            ->when($status === "pending", function ($query) {
                $query
                    ->whereIn("status", ["Pending", "For Approval"])
                    ->whereNull("deleted_at")
                    ->whereNull("cancelled_at");
            })
            ->when($status === "approved", function ($query) {
                $query
                    ->whereNotNull("approved_at")
                    ->whereNull("rejected_at")
                    ->whereNull("voided_at")
                    ->whereNull("cancelled_at")
                    ->where("status", "For Receiving");
            })
            ->when($status === "rejected", function ($query) {
                $query->whereNotNull("rejected_at")->where("status", "Reject");
            })
            ->when($status === "cancelled", function ($query) {
                $query
                    ->where("status", "Cancelled")
                    ->whereNotNull("cancelled_at");
            })
            ->when($status === "return_po", function ($query) {
                $query->where("status", "Return");
            })
            ->when($status === "partial_received", function ($query) {
                $query
                    ->where("status", "For Receiving")
                    ->whereHas("po_items", function ($subQuery) {
                        $subQuery
                            ->where("quantity_serve", ">", 0) // Use `where` for value comparison
                            ->whereColumn("quantity_serve", "<", "quantity"); // Use `whereColumn` for column comparison
                    })
                    ->whereNull("rejected_at")
                    ->whereNull("cancelled_at")
                    ->whereNotNull("approved_at");
            });
    }
}
