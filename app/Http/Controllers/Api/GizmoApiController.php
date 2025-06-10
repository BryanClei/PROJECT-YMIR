<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\POTransaction;
use App\Models\JOPOTransaction;
use App\Http\Controllers\Controller;

class GizmoApiController extends Controller
{
    public function index(Request $request)
    {
        $po_transaction = POTransaction::with("order.uom")
            ->whereIn("status", ["For Receiving", "Approved"])
            ->whereNotNull("approved_at")
            ->when($request->filled("approved_date_from"), function (
                $query
            ) use ($request) {
                $query->whereDate(
                    "approved_at",
                    ">=",
                    $request->input("approved_date_from")
                );
            })
            ->when($request->filled("approved_date_to"), function ($query) use (
                $request
            ) {
                $query->whereDate(
                    "approved_at",
                    "<=",
                    $request->input("approved_date_to")
                );
            })
            ->get();

        return $po_transaction;
    }

    public function job_order(Request $request)
    {
        $po_transaction = JOPOTransaction::with("jo_po_orders.uom")
            ->whereIn("status", ["For Receiving", "Approved"])
            ->whereNotNull("approved_at")
            ->when($request->filled("approved_date_from"), function (
                $query
            ) use ($request) {
                $query->whereDate(
                    "approved_at",
                    ">=",
                    $request->input("approved_date_from")
                );
            })
            ->when($request->filled("approved_date_to"), function ($query) use (
                $request
            ) {
                $query->whereDate(
                    "approved_at",
                    "<=",
                    $request->input("approved_date_to")
                );
            })
            ->get();

        return $po_transaction;
    }
}
