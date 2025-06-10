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
        $user = auth()->user()->id;

        $userLayers = JobHistory::where("approver_id", $user)
            ->pluck("layer", "jo_id")
            ->toArray();

        $this->builder
            ->when($status === "pending", function ($query) use (
                $userLayers,
                $user
            ) {
                $query
                    ->where(function ($q) use ($userLayers) {
                        foreach ($userLayers as $joId => $layer) {
                            $q->orWhere(function ($sub) use ($joId, $layer) {
                                $sub->where("id", $joId)->where(
                                    "layer",
                                    $layer
                                );
                            });
                        }
                    })
                    ->where(function ($query) {
                        $query
                            ->where("status", "Pending")
                            ->orWhere("status", "For Approval");
                    })
                    ->whereNull("voided_at")
                    ->whereNull("cancelled_at")
                    ->whereNull("rejected_at")
                    ->whereHas("approver_history", function ($query) use (
                        $user,
                        $userLayers
                    ) {
                        $query
                            ->whereNull("approved_at")
                            ->where("approver_id", $user)
                            ->whereIn("jo_id", array_keys($userLayers))
                            ->whereIn("layer", array_values($userLayers));
                    });
            })

            ->when($status === "approved", function ($query) use ($user) {
                $query
                    ->whereNull("cancelled_at")
                    ->whereNull("voided_at")
                    ->whereHas("approver_history", function ($query) use (
                        $user
                    ) {
                        $query
                            ->where("approver_id", $user)
                            ->whereNotNull("approved_at");
                    });
            })

            ->when($status === "rejected", function ($query) use ($user) {
                $query
                    ->whereNull("voided_at")
                    ->whereHas("approver_history", function ($query) use (
                        $user
                    ) {
                        $query
                            ->where("approver_id", $user)
                            ->whereNotNull("rejected_at");
                    });
            });
    }
}
