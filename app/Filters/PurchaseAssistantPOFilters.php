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
        "business_unit_name",
        "company_name",
        "department_name",
        "department_unit_name",
        "location_name",
        "sub_unit_name",
        "account_title_name",
        "supplier_name",
        "module_name",
    ];

    protected array $relationSearch = [
        "pr_transaction" => ["pr_year_number_id"],
    ];

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
                    ->with([
                        "po_items" => function ($query) {
                            $query->whereNull("deleted_at");
                        },
                    ])
                    ->whereNotNull("approved_at")
                    ->whereNull("rejected_at")
                    ->whereNull("voided_at")
                    ->whereNull("cancelled_at")
                    ->where("status", "For Receiving")
                    ->whereDoesntHave("po_items", function ($query) {
                        $query
                            ->whereNull("deleted_at")
                            ->where("quantity_serve", ">", 0);
                    });
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
                    ->with([
                        "po_items" => function ($query) {
                            $query->whereNull("deleted_at");
                        },
                    ])
                    ->where("status", "For Receiving")
                    ->whereHas("po_items", function ($subQuery) {
                        // At least one item has been received (fully or partially)
                        $subQuery->where("quantity_serve", ">", 0);
                    })
                    ->whereHas("po_items", function ($subQuery) {
                        // At least one item is not fully received
                        $subQuery->whereColumn(
                            "quantity_serve",
                            "<",
                            "quantity"
                        );
                    })
                    ->whereNull("rejected_at")
                    ->whereNull("cancelled_at")
                    ->whereNotNull("approved_at");
            })
            ->when($status === "received", function ($query) {
                $query
                    ->with([
                        "po_items" => function ($query) {
                            $query->whereNull("deleted_at");
                        },
                    ])
                    ->whereNotNull("approved_at")
                    ->whereNull("rejected_at")
                    ->whereNull("voided_at")
                    ->whereNull("cancelled_at")
                    ->where("status", "For Receiving")
                    ->whereHas("po_items", function ($query) {
                        $query
                            ->whereNull("deleted_at")
                            ->whereColumn("quantity_serve", ">=", "quantity");
                    });
            });
    }
}
