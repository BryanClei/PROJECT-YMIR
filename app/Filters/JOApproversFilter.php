<?php

namespace App\Filters;

use App\Models\User;
use App\Models\JobHistory;
use Essa\APIToolKit\Filters\QueryFilters;

class JOApproversFilter extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "jo_year_number_id",
        "jo_number",
        "jo_description",
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
        "asset",
        "module_name",
        "total_price",
        "status",
        "layer",
        "description",
        "reason",
        "approved_at",
        "rejected_at",
        "voided_at",
        "cancelled_at",
        "approver_id",
        "helpdesk_id",
    ];

    public function status($status)
    {
        $user = Auth()->user()->id;
        $user_id = User::where("id", $user)
            ->get()
            ->first();

        $jo_id = JobHistory::where("approver_id", $user)
            ->get()
            ->pluck("jo_id");
        $layer = JobHistory::where("approver_id", $user)
            ->get()
            ->pluck("layer");

        $this->builder
            ->when($status == "pending", function ($query) use (
                $jo_id,
                $layer
            ) {
                $query

                    ->whereIn("id", $jo_id)
                    ->whereIn("layer", $layer)
                    ->where(function ($query) {
                        $query
                            ->where("status", "Pending")
                            ->orWhere("status", "For Approval");
                    })
                    ->whereNull("voided_at")
                    ->whereNull("cancelled_at")
                    ->whereNull("rejected_at");
            })
            ->when($status == "rejected", function ($query) use (
                $jo_id,
                $layer
            ) {
                $query
                    ->whereIn("id", $jo_id)
                    ->whereIn("layer", $layer)
                    ->whereNull("voided_at")
                    ->whereNotNull("rejected_at");
            })

            ->when($status == "approved", function ($query) use (
                $jo_id,
                $layer,
                $user_id
            ) {
                $query
                    ->whereIn("id", $jo_id)
                    ->whereNull("cancelled_at")
                    ->whereNull("voided_at")
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
