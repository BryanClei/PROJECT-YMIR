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
        "po_year_number_id",
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

        // Fetch all PO histories for the current user
        $approver_histories = PoHistory::where("approver_id", $user)->get();

        // Extract unique PO IDs and layers
        $po_ids = $approver_histories->pluck("po_id")->unique();
        $layers = $approver_histories->pluck("layer")->unique();

        $this->builder
            ->when($status == "pending", function ($query) use (
                $user,
                $po_ids
            ) {
                $query
                    ->whereIn("id", $po_ids)
                    ->where(function ($query) {
                        $query
                            ->where("status", "Pending")
                            ->orWhere("status", "For Approval");
                    })
                    ->whereNull("voided_at")
                    ->whereNull("cancelled_at")
                    ->whereNull("rejected_at")
                    ->whereHas("approver_history", function ($query) use (
                        $user
                    ) {
                        // Only show transactions where the current user is the approver
                        // for the current layer AND hasn't approved yet
                        $query
                            ->where("approver_id", $user)
                            ->whereNull("approved_at");
                    })
                    // Additional condition: ensure the transaction's current layer
                    // matches the user's layer in the approval history
                    ->where(function ($query) use ($user) {
                        $query->whereRaw(
                            "layer = (
                        SELECT layer 
                        FROM po_history 
                        WHERE po_history.po_id = po_transactions.id 
                        AND po_history.approver_id = ? 
                        AND po_history.approved_at IS NULL
                        LIMIT 1
                    )",
                            [$user]
                        );
                    });
            })
            ->when($status == "rejected", function ($query) use (
                $po_ids,
                $layers
            ) {
                $query
                    ->whereIn("id", $po_ids)
                    ->whereIn("layer", $layers)
                    ->whereNull("voided_at")
                    ->whereNotNull("rejected_at");
            })
            ->when($status == "approved", function ($query) use (
                $po_ids,
                $user
            ) {
                $query
                    ->whereIn("id", $po_ids)
                    ->whereHas("approver_history", function ($query) use (
                        $user
                    ) {
                        $query
                            ->where("approver_id", $user)
                            ->whereNotNull("approved_at");
                    });
            });
    }

    // public function status($status)
    // {
    //     $user = Auth()->user()->id;

    //     $user_id = User::where("id", $user)
    //         ->get()
    //         ->first();

    //     $po_id = PoHistory::where("approver_id", $user_id->id)
    //         ->get()
    //         ->pluck("po_id");
    //     $layer = PoHistory::where("approver_id", $user_id->id)
    //         ->get()
    //         ->pluck("layer");
    //     $approver_histories = PoHistory::where("approver_id", $user)->get();

    //     $this->builder
    //         ->when($status == "pending", function ($query) use (
    //             $po_id,
    //             $layer,
    //             $approver_histories
    //         ) {
    //             $query
    //                 ->where(function ($query) use ($approver_histories) {
    //                     foreach ($approver_histories as $history) {
    //                         $query->orWhere(function ($subQuery) use (
    //                             $history
    //                         ) {
    //                             $subQuery
    //                                 ->where("id", $history->po_id)
    //                                 ->where("layer", $history->layer)
    //                                 ->whereHas("approver_history", function (
    //                                     $historyQuery
    //                                 ) use ($history) {
    //                                     $historyQuery
    //                                         ->where("layer", $history->layer)
    //                                         ->whereNull("approved_at");
    //                                 });
    //                         });
    //                     }
    //                 })
    //                 ->where(function ($query) {
    //                     $query
    //                         ->where("status", "Pending")
    //                         ->orWhere("status", "For Approval");
    //                 })
    //                 ->whereNull("voided_at")
    //                 ->whereNull("cancelled_at")
    //                 ->whereNull("rejected_at")
    //                 ->whereHas("approver_history", function ($query) {
    //                     $query->whereNull("approved_at");
    //                 });
    //         })
    //         ->when($status == "rejected", function ($query) use (
    //             $po_id,
    //             $layer
    //         ) {
    //             $query
    //                 ->whereIn("id", $po_id)
    //                 ->whereIn("layer", $layer)
    //                 ->whereNull("voided_at")
    //                 ->whereNotNull("rejected_at");
    //         })

    //         ->when($status == "approved", function ($query) use (
    //             $po_id,
    //             $layer,
    //             $user_id
    //         ) {
    //             $query
    //                 ->whereIn("id", $po_id)
    //                 ->whereHas("approver_history", function ($query) use (
    //                     $user_id
    //                 ) {
    //                     $query
    //                         ->whereIn("approver_id", $user_id)
    //                         ->whereNotNull("approved_at");
    //                 });
    //         });
    // }
}
