<?php

namespace App\Http\Controllers\Api;

use App\Models\POItems;
use App\Models\Warehouse;
use App\Response\Message;
use Illuminate\Http\Request;
use App\Models\POTransaction;
use App\Models\PRTransaction;
use App\Models\RRTransaction;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Resources\ETDApiResource;

class ETDApiController extends Controller
{
    public function index(Request $request)
    {
        $warehouse_name = $request->system_name;
        $from_date = $request->from;
        $to_date = $request->to;

        $warehouse = Warehouse::where("name", $warehouse_name)
            ->get()
            ->first();

        if (!$warehouse) {
            return GlobalFunction::notFound(
                " Warehouse or " . Message::NOT_FOUND
            );
        }
        $w_id = $warehouse->id;

        $rr_transactions = RRTransaction::with([
            "pr_transaction.order",
            "po_transaction.order",
            "rr_orders",
        ])
            ->whereHas("pr_transaction.order", function ($query) use ($w_id) {
                $query->where("warehouse_id", $w_id);
            })
            ->when($from_date || $to_date, function ($query) use (
                $from_date,
                $to_date
            ) {
                return $query
                    ->when($from_date, function ($q) use ($from_date) {
                        return $q->whereDate("created_at", ">=", $from_date);
                    })
                    ->when($to_date, function ($q) use ($to_date) {
                        return $q->whereDate("created_at", "<=", $to_date);
                    });
            })
            ->get();

        if ($rr_transactions->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return $rr_transactions;
    }

    // public function index(Request $request)
    // {
    //     $warehouse_name = $request->system_name;
    //     $from = $request->from;
    //     $to = $request->to;

    //     $warehouse = Warehouse::where("name", $warehouse_name)
    //         ->get()
    //         ->first();

    //     if (!$warehouse) {
    //         return GlobalFunction::notFound(
    //             " Warehouse or " . Message::NOT_FOUND
    //         );
    //     }
    //     $w_id = $warehouse->id;

    //     $data = POItems::with([
    //         "po_transaction",
    //         "po_transaction.pr_transaction",
    //     ])
    //         ->whereHas("po_transaction", function ($query) use (
    //             $w_id,
    //             $from,
    //             $to
    //         ) {
    //             $query
    //                 ->where("type_name", "Inventoriable")
    //                 ->where("status", "For Receiving")
    //                 ->where("warehouse_id", $w_id)
    //                 ->whereNotNull("approved_at")
    //                 ->when($from, function ($query) use ($from) {
    //                     return $query->whereDate("approved_at", ">=", $from);
    //                 })
    //                 ->when($to, function ($query) use ($to) {
    //                     return $query->whereDate("approved_at", "<=", $to);
    //                 });
    //         })
    //         ->get();

    //     if ($data->isEmpty()) {
    //         return GlobalFunction::notFound(Message::NOT_FOUND);
    //     }

    //     return ETDApiResource::collection($data);
    // }
}
