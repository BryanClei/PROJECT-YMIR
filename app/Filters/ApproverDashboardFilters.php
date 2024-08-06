<?php

namespace App\Filters;

use App\Models\User;
use App\Models\PoHistory;
use Essa\APIToolKit\Filters\QueryFilters;

class ApproverDashboardFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
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
        "total_item_price",
    ];

    public function status($status)
    {
        $user = Auth()->user()->id;

        $user_id = User::where("id", $user)
            ->get()
            ->first();

        $po_id = PoHistory::where("approver_id", $user)
            ->get()
            ->pluck("po_id");
        $layer = PoHistory::where("approver_id", $user)
            ->get()
            ->pluck("layer");

        $this->builder
            ->when($status == "pending", function ($query) use (
                $po_id,
                $layer
            ) {
                $query

                    ->whereIn("id", $po_id)
                    ->whereIn("layer", $layer)
                    ->where(function ($query) {
                        $query
                            ->where("status", "Pending")
                            ->orWhere("status", "For Approval");
                    })
                    ->whereNull("voided_at")
                    ->whereNull("cancelled_at")
                    ->whereNull("rejected_at")
                    ->whereHas("approver_history", function ($query) {
                        $query->whereNull("approved_at");
                    });
            })
            ->when($status == "rejected", function ($query) use (
                $po_id,
                $layer
            ) {
                $query
                    ->whereIn("id", $po_id)
                    ->whereIn("layer", $layer)
                    ->whereNull("voided_at")
                    ->whereNotNull("rejected_at");
            })

            ->when($status == "approved", function ($query) use (
                $po_id,
                $layer,
                $user_id
            ) {
                $query
                    ->whereIn("id", $po_id)
                    ->whereHas("approver_history", function ($query) use (
                        $user_id
                    ) {
                        $query
                            ->whereIn("approver_id", $user_id)
                            ->whereNotNull("approved_at");
                    });
            });
    }
}
