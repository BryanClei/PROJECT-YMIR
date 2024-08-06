<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class AssetsTransactionFilters extends QueryFilters
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
            ->when($status === "pending" || $status === "syncing", function (
                $query
            ) {
                $query
                    ->where("status", "Pending")
                    ->orWhere("status", "For Approval");
            })
            ->when($status === "for_po_pending", function ($query) {
                $query
                    ->whereNotNull("for_po_only")
                    ->whereNotNull("for_po_only_id")
                    ->where("status", "Pending")
                    ->orWhere("status", "For Approval");
            })
            ->when($status === "cancel", function ($query) {
                $query->where("status", "Cancelled");
            })
            ->when($status === "cancelled", function ($query) {
                $query->where("status", "Cancelled");
            })
            ->when($status === "voided", function ($query) {
                $query->whereNotNull("voided_at");
            })
            ->when($status === "rejected", function ($query) {
                $query->whereNotNull("rejected_at");
            })
            ->when($status === "approved", function ($query) {
                $query
                    ->whereNull("cancelled_at")
                    ->whereNull("voided_at")
                    ->whereNotNull("approved_at")
                    ->where("status", "Approved");
            })
            ->when($status === "pr_approved", function ($query) {
                $query
                    ->where("status", "Approved")
                    ->whereNull("cancelled_at")
                    ->whereNull("voided_at")
                    ->whereHas("approver_history", function ($query) {
                        $query->whereNotNull("approved_at");
                    });
            })
            ->when($status === "return_pr", function ($query) {
                $query->where("status", "Return");
            });
    }
}
