<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class BuyerPOFilter extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "pr_number",
        "pr_description",
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
                    ->whereNull("rejected_at");
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
            ->when($status === "pending_to_receive", function ($query) {
                $query
                    ->where("status", "For Receiving")
                    ->whereHas("order", function ($subQuery) {
                        $subQuery
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
                            "=",
                            "quantity"
                        );
                    });
            });
    }
}
