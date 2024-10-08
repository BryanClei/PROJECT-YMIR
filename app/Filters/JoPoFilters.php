<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class JoPoFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
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
        "status",
        "layer",
        "description",
        "reason",
        "asset",
        "sgp",
        "f1",
        "f2",
    ];

    public function status($status)
    {
        $this->builder
            ->when($status === "approved", function ($query) {
                $query
                    ->where("status", "For Receiving")
                    ->orWhere("status", "Approved")
                    ->whereNull("cancelled_at")
                    ->whereNull("rejected_at")
                    ->whereNull("voided_at")
                    ->whereHas("jo_approver_history", function ($query) {
                        $query->whereNotNull("approved_at");
                    });
            })
            ->when($status === "pending", function ($query) {
                $query
                    ->where("status", "Pending")
                    ->orWhere("status", "For Approval")
                    ->whereNull("cancelled_at")
                    ->whereNull("rejected_at")
                    ->whereNull("voided_at");
            })
            ->when($status === "cancelled", function ($query) {
                $query
                    ->where("status", "Cancelled")
                    ->whereNotNull("cancelled_at")
                    ->whereNull("approved_at");
            })
            ->when($status === "voided", function ($query) {
                $query->where("status", "Voided");
            })
            ->when($status === "rejected", function ($query) {
                $query->where("status", "Reject")->whereNotNull("rejected_at");
            })
            ->when($status === "for_receiving", function ($query) {
                $query
                    ->with([
                        "jo_po_orders" => function ($query) {
                            $query->whereColumn(
                                "quantity",
                                "<>",
                                "quantity_serve"
                            );
                        },
                    ])
                    ->where("status", "For Receiving")
                    ->whereNull("cancelled_at")
                    ->whereNull("rejected_at")
                    ->whereNull("voided_at")
                    ->whereHas("jo_approver_history", function ($query) {
                        $query->whereNotNull("approved_at");
                    })
                    ->whereHas("jo_po_orders", function ($query) {
                        $query->whereColumn("quantity", "<>", "quantity_serve");
                    });
            })
            ->when($status === "return_po", function ($query) {
                $query->where("status", "Return")->whereNull("rejected_at");
            });
    }
}
