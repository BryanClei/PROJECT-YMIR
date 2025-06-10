<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class BuyerJOPOFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "po_year_number_id",
        "jo_number",
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
        "module_name",
        "supplier_id",
        "supplier_name",
        "description",
        "reason",
    ];

    public function status($status)
    {
        $user_id = auth()->user()->id;

        $this->builder
            ->when($status === "pending", function ($query) use ($user_id) {
                $query
                    ->whereHas("jo_po_orders", function ($query) use (
                        $user_id
                    ) {
                        $query->where("buyer_id", $user_id);
                    })
                    ->whereIn("status", ["Pending", "For Approval"])
                    ->whereNull("approved_at")
                    ->whereNull("rejected_at")
                    ->whereNull("cancelled_at");
            })
            ->when($status === "approved", function ($query) use ($user_id) {
                $query
                    ->whereHas("jo_po_orders", function ($query) use (
                        $user_id
                    ) {
                        $query->where("buyer_id", $user_id);
                    })
                    ->whereDoesntHave("jo_rr_transaction")
                    ->where("status", "For Receiving")
                    ->whereNotNull("approved_at");
            })
            ->when($status === "rejected", function ($query) use ($user_id) {
                $query
                    ->whereHas("jo_po_orders", function ($query) use (
                        $user_id
                    ) {
                        $query->where("buyer_id", $user_id);
                    })
                    ->where("status", "Reject")
                    ->whereNull("cancelled_at")
                    ->whereNotNull("rejected_at");
            })
            ->when($status === "cancelled", function ($query) use ($user_id) {
                $query
                    ->whereHas("jo_po_orders", function ($query) use (
                        $user_id
                    ) {
                        $query->where("buyer_id", $user_id);
                    })
                    ->where("status", "Cancelled")
                    ->whereNull("rejected_at")
                    ->whereNotNull("cancelled_at");
            })
            ->when($status === "pending_to_receive", function ($query) use (
                $user_id
            ) {
                $query
                    ->whereHas("jo_po_orders", function ($subQuery) use (
                        $user_id
                    ) {
                        $subQuery
                            ->where("buyer_id", $user_id)
                            ->where("quantity_serve", ">", 0)
                            ->whereColumn("quantity_serve", "<", "quantity");
                    })
                    ->where("status", "For Receiving")
                    ->whereNotNull("approved_at")
                    ->whereNull("rejected_at")
                    ->whereNull("cancelled_at");
            })
            ->when($status === "completed", function ($query) use ($user_id) {
                $query
                    ->whereHas("jo_po_orders", function ($query) use (
                        $user_id
                    ) {
                        $query
                            ->where("buyer_id", $user_id)
                            ->whereColumn("quantity_serve", ">=", "quantity");
                    })
                    ->where("status", "For Receiving")
                    ->whereNotNull("approved_at")
                    ->whereNull("rejected_at")
                    ->whereNull("cancelled_at");
            });
    }
}
