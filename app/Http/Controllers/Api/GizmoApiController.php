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
        $po_transaction = POTransaction::with([
            "order.uom",
            "approver_history",
            "vladimir_user", // make sure to eager load
            "regular_user",
            "log_history",
            "log_history.users",
        ])
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

        // Transform each transaction
        $transformed = $po_transaction->map(function ($t) {
            $t->users =
                $t->module_name === "Asset" && $t->vladimir_user
                    ? [
                        "id" => $t->vladimir_user->id,
                        "employee_id" => $t->vladimir_user->employee_id,
                        "username" => $t->vladimir_user->username,
                        "first_name" => $t->vladimir_user->firstname,
                        "last_name" => $t->vladimir_user->lastname,
                    ]
                    : ($t->regular_user
                        ? [
                            "prefix_id" => $t->regular_user->prefix_id,
                            "id_number" => $t->regular_user->id_number,
                            "first_name" => $t->regular_user->first_name,
                            "middle_name" => $t->regular_user->middle_name,
                            "last_name" => $t->regular_user->last_name,
                            "mobile_no" => $t->regular_user->mobile_no,
                        ]
                        : []);

            unset($t->vladimir_user, $t->regular_user); // optional if you don't want to expose

            return $t;
        });

        return response()->json($transformed);
    }

    public function job_order(Request $request)
    {
        $po_transaction = JOPOTransaction::with(
            "users",
            "jo_po_orders.uom",
            "jo_approver_history",
            "log_history",
            "log_history.users"
        )
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
