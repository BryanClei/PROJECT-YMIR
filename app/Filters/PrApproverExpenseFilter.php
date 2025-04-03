<?php

namespace App\Filters;

use App\Models\User;
use App\Models\PrHistory;
use Essa\APIToolKit\Filters\QueryFilters;

class PrApproverExpenseFilter extends QueryFilters
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
        $user = Auth()->user()->id;
        $user_id = User::where("id", $user)
            ->get()
            ->first();

        $pr_id = PrHistory::where("approver_id", $user)
            ->get()
            ->pluck("pr_id");
        $layer = PrHistory::where("approver_id", $user)
            ->get()
            ->pluck("layer");
        $userLayers = PrHistory::where("approver_id", $user)
            ->pluck("layer", "pr_id") // PR ID => Layer assigned to the user
            ->toArray();

        // $this->builder
        //     ->when($status == "pending", function ($query) use (
        //         $approver_histories
        //     ) {
        //         $query
        //             ->where(function ($query) use ($approver_histories) {
        //                 foreach ($approver_histories as $history) {
        //                     $query->orWhere(function ($subQuery) use (
        //                         $history
        //                     ) {
        //                         $subQuery
        //                             ->where("id", $history->pr_id)
        //                             ->where("layer", $history->layer)
        //                             ->whereHas("approver_history", function (
        //                                 $historyQuery
        //                             ) use ($history) {
        //                                 $historyQuery
        //                                     ->where("layer", $history->layer)
        //                                     ->where(
        //                                         "approver_id",
        //                                         $history->approver_id
        //                                     )
        //                                     ->whereNull("approved_at");
        //                             });
        //                     });
        //                 }
        //             })
        //             ->where(function ($query) {
        //                 $query
        //                     ->where("status", "Pending")
        //                     ->orWhere("status", "For Approval");
        //             })
        //             ->whereNull("voided_at")
        //             ->whereNull("cancelled_at")
        //             ->whereNull("rejected_at");
        //     })
        // $this->builder
        //     ->when($status == "pending", function ($query) use (
        //         $pr_id,
        //         $layer,
        //         $user
        //     ) {
        //         $query
        //             ->whereIn("id", $pr_id)
        //             ->whereIn("layer", $layer)
        //             ->where(function ($query) {
        //                 $query
        //                     ->where("status", "Pending")
        //                     ->orWhere("status", "For Approval");
        //             })
        //             ->whereNull("voided_at")
        //             ->whereNull("cancelled_at")
        //             ->whereNull("rejected_at")
        //             ->whereHas("approver_history", function ($query) use (
        //                 $user
        //             ) {
        //                 $query
        //                     ->whereNull("approved_at")
        //                     ->where("approver_id", $user);
        //             });
        //     })

        $this->builder
            ->when($status == "pending", function ($query) use (
                $user,
                $userLayers
            ) {
                $query
                    ->whereIn("status", ["Pending", "For Approval"])
                    ->whereHas("approver_history", function ($query) use (
                        $user,
                        $userLayers
                    ) {
                        $query
                            ->whereNull("approved_at")
                            ->where("approver_id", $user)
                            ->whereIn("pr_id", array_keys($userLayers))
                            ->whereIn("layer", array_values($userLayers));
                    })
                    ->where(function ($query) use ($userLayers) {
                        foreach ($userLayers as $prId => $layer) {
                            $query->orWhere(function ($subQuery) use (
                                $prId,
                                $layer
                            ) {
                                $subQuery
                                    ->where("id", $prId)
                                    ->where("layer", $layer);
                            });
                        }
                    });
            })
            ->when($status == "rejected", function ($query) use (
                $pr_id,
                $layer
            ) {
                $query
                    ->whereIn("id", $pr_id)
                    ->whereIn("layer", $layer)
                    ->whereNull("voided_at")
                    ->whereNotNull("rejected_at");
            })

            ->when($status == "approved", function ($query) use (
                $pr_id,
                $layer,
                $user_id
            ) {
                $query
                    ->whereIn("id", $pr_id)
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
