<?php

namespace App\Helpers;

use App\Models\PoHistory;
use App\Models\JoPoHistory;
use App\Models\POTransaction;
use App\Models\JOPOTransaction;

class BadgeHelperFunctions
{
    const STATUS_PENDING = "Pending";
    const STATUS_FOR_APPROVAL = "For Approval";

    public static function poId($user)
    {
        return PoHistory::where("approver_id", $user)
            ->get()
            ->pluck("po_id");
    }

    public static function layer($user)
    {
        return PoHistory::where("approver_id", $user)
            ->get()
            ->pluck("layer");
    }

    public static function getPrCount($poId, $layer, $typeName)
    {
        $user = Auth()->user()->id;

        $approver_histories = PoHistory::where("approver_id", $user)->get();
        return POTransaction::where("type_name", $typeName)
            ->whereIn("id", $poId)
            ->whereIn("layer", $layer)
            ->where(function ($query) {
                $query
                    ->where("status", self::STATUS_PENDING)
                    ->orWhere("status", self::STATUS_FOR_APPROVAL);
            })
            ->whereNull("voided_at")
            ->whereNull("cancelled_at")
            ->whereNull("rejected_at")
            ->whereHas("approver_history", function ($query) {
                $query->whereNull("approved_at");
            })
            ->count();
    }

    public static function joPoId($approver_histories)
    {
        return $po_ids = $approver_histories->pluck("jo_po_id")->toArray();
    }

    public static function joLayer($approver_histories)
    {
        return $approver_histories->pluck("layer")->toArray();
    }

    public static function poJobOrderCount($jo_po_id, $jo_layer)
    {
        return JOPOTransaction::whereIn("id", $jo_po_id)
            ->whereIn("layer", $jo_layer)
            ->where(function ($query) {
                $query
                    ->where("status", "Pending")
                    ->orWhere("status", "For Approval");
            })
            ->whereNull("voided_at")
            ->whereNull("cancelled_at")
            ->whereNull("rejected_at")
            ->whereHas("jo_approver_history", function ($query) {
                $query->whereNull("approved_at");
            })
            ->count();
    }

    public static function forReceiving()
    {
        return POTransaction::with(
            "order",
            "approver_history",
            "rr_transaction",
            "rr_transaction.rr_orders"
        )

            ->with([
                "order" => function ($query) {
                    $query->whereColumn("quantity", ">", "quantity_serve");
                },
            ])
            ->where("module_name", "Asset")
            ->where("status", "For Receiving")
            ->whereNull("cancelled_at")
            ->whereNull("voided_at")
            ->whereHas("approver_history", function ($query) {
                $query->whereNotNull("approved_at");
            })
            ->whereHas("order", function ($query) {
                $query->whereColumn("quantity", ">", "quantity_serve");
            })
            ->withoutTrashed()
            ->count();
    }

    public static function forReceivingUser($user_id)
    {
        return POTransaction::with(
            "order",
            "approver_history",
            "rr_transaction",
            "rr_transaction.rr_orders"
        )
            ->with([
                "order" => function ($query) {
                    $query->whereColumn("quantity", ">", "quantity_serve");
                },
            ])
            ->where("module_name", "!==", "Asset")
            ->where("user_id", $user_id)
            ->where("status", "For Receiving")
            ->whereNull("cancelled_at")
            ->whereNull("voided_at")
            ->whereHas("approver_history", function ($query) {
                $query->whereNotNull("approved_at");
            })
            ->whereHas("order", function ($query) {
                $query->whereColumn("quantity", ">", "quantity_serve");
            })
            ->count();
    }

    public static function rrJobOrderCount()
    {
        return JOPOTransaction::where("module_name", "Job Order")
            ->where("status", "For Receiving")
            ->whereHas("jo_po_orders", function ($query) {
                $query->whereColumn("quantity", ">", "quantity_serve");
            })
            ->count();
    }

    public static function rrJobOrderCountUser($user_id)
    {
        return JOPOTransaction::where("module_name", "Job Order")
            ->whereHas("jo_po_orders", function ($query) {
                $query->whereColumn("quantity", ">", "quantity_serve");
            })
            ->whereHas("jo_transaction", function ($query) use ($user_id) {
                $query->where("user_id", $user_id);
            })
            ->where("status", "For Receiving")
            ->whereNotNull("approved_at")
            ->count();
    }
}
