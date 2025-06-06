<?php

namespace App\Filters;

use App\Models\User;
use App\Models\JoPoHistory;
use Essa\APIToolKit\Filters\QueryFilters;

class ApproverDashboardJOPOFilters extends QueryFilters
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
        $user = Auth()->user()->id;

        $user_id = User::where("id", $user)
            ->get()
            ->first();

        $po_id = JoPoHistory::where("approver_id", $user)
            ->get()
            ->pluck("jo_po_id");
        $layer = JoPoHistory::where("approver_id", $user)
            ->get()
            ->pluck("layer");
        $approver_histories = JoPoHistory::where("approver_id", $user)->get();
        $user_layer = JoPoHistory::where("approver_id", $user)->pluck("layer");

        $this->builder
            ->when($status == "pending", function ($query) use (
                $approver_histories
            ) {
                $query
                    ->where(function ($query) use ($approver_histories) {
                        foreach ($approver_histories as $history) {
                            $query->orWhere(function ($subQuery) use (
                                $history
                            ) {
                                $subQuery
                                    ->where("id", $history->jo_po_id)
                                    ->where("layer", $history->layer)
                                    ->whereHas("jo_approver_history", function (
                                        $historyQuery
                                    ) use ($history) {
                                        $historyQuery
                                            ->where("layer", $history->layer)
                                            ->whereNull("approved_at");
                                    });
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
                    ->whereNull("rejected_at");
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
                    ->whereIn("status", [
                        "Approved",
                        "For Receiving",
                        "Approved",
                    ])
                    ->whereHas("jo_approver_history", function ($query) use (
                        $user_id
                    ) {
                        $query
                            ->whereIn("approver_id", $user_id)
                            ->whereNotNull("approved_at");
                    });
            });
    }
}
