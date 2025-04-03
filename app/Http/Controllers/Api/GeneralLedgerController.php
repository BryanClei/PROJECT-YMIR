<?php

namespace App\Http\Controllers\Api;

use App\Models\RROrders;
use App\Response\Message;
use App\Models\JORROrders;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\RROrdersReports;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Resources\IntegrationGLResource;
use App\Http\Resources\IntegrationGLJORRResource;

class GeneralLedgerController extends Controller
{
    public function index()
    {
        $rr_transaction = RROrders::with(
            "rr_transaction.po_transaction"
        )->get();

        return $rr_transaction;
    }

    public function integration_index(Request $request)
    {
        $adjustment_month = $request->adjustment_month;
        $date = Carbon::parse($adjustment_month);

        $month = $date->month;
        $year = $date->year;

        $rr_transaction = RROrders::with("rr_transaction")
            ->when($adjustment_month, function ($query) use ($month, $year) {
                return $query
                    ->whereYear("created_at", $year)
                    ->whereMonth("created_at", $month);
            })
            ->whereHas("rr_transaction", function ($query) {
                $query->whereHas("po_transaction", function ($subQuery) {
                    $subQuery->whereNot("module_name", "Expense");
                });
            })
            ->get();

        $collected = IntegrationGLResource::collection($rr_transaction);

        return $collected;
    }

    public function general_ledger_index_multiple_po(Request $request)
    {
        $adjustment_month = $request->adjustment_month;
        $date = Carbon::parse($adjustment_month);
        $from_date = $request->from;
        $to_date = $request->to;

        $month = $date->month;
        $year = $date->year;

        $rr_transaction = RROrdersReports::whereHas("po_transaction", function (
            $query
        ) {
            $query->whereNot("module_name", "Expense")->withTrashed();
        })
            ->with([
                "rr_transaction.po_transaction" => function ($query) {
                    $query->whereNot("module_name", "Expense")->withTrashed();
                },
            ])
            ->when($adjustment_month, function ($query) use ($month, $year) {
                return $query
                    ->whereYear("delivery_date", $year)
                    ->whereMonth("delivery_date", $month);
            })
            ->when($from_date, function ($query) use ($from_date) {
                $query->where("delivery_date", ">=", $from_date);
            })
            ->when($to_date, function ($query) use ($to_date) {
                $query->where("delivery_date", "<=", $to_date);
            })
            ->whereNull("deleted_at")
            ->get();

        $collected = IntegrationGLResource::collection(
            $rr_transaction
        )->toArray([]);

        $result = array_merge(...$collected);

        return $result;
    }

    public function general_ledger_index_multiple_jo(Request $request)
    {
        $adjustment_month = $request->adjustment_month;
        $date = Carbon::parse($adjustment_month);
        $from_date = $request->from;
        $to_date = $request->to;

        $month = $date->month;
        $year = $date->year;

        $rr_transaction = JORROrders::whereHas("jo_po_transaction")
            ->with("jo_rr_transaction.jo_po_transactions")
            ->when($adjustment_month, function ($query) use ($month, $year) {
                return $query
                    ->whereYear("delivery_date", $year)
                    ->whereMonth("delivery_date", $month);
            })
            ->when($from_date, function ($query) use ($from_date) {
                $query->where("delivery_date", ">=", $from_date);
            })
            ->when($to_date, function ($query) use ($to_date) {
                $query->where("delivery_date", "<=", $to_date);
            })
            ->whereNull("deleted_at")
            ->get();

        $collected = IntegrationGLJORRResource::collection(
            $rr_transaction
        )->toArray([]);

        $result = array_merge(...$collected);

        return $result;
    }

    // public function general_ledger_index_multiple_po(Request $request)
    // {
    //     $adjustment_month = $request->adjustment_month;
    //     $date = Carbon::parse($adjustment_month);
    //     $from_date = $request->from;
    //     $to_date = $request->to;

    //     $month = $date->month;
    //     $year = $date->year;

    //     $rr_transaction = RROrders::whereHas("po_transaction", function (
    //         $query
    //     ) {
    //         $query->whereNot("module_name", "Expense");
    //     })
    //         ->with([
    //             "rr_transaction.po_transaction" => function ($query) {
    //                 $query->whereNot("module_name", "Expense");
    //             },
    //         ])
    //         ->when($adjustment_month, function ($query) use ($month, $year) {
    //             return $query
    //                 ->whereYear("created_at", $year)
    //                 ->whereMonth("created_at", $month);
    //         })
    //         ->when($from_date, function ($query) use ($from_date) {
    //             $query->where("created_at", ">=", $from_date);
    //         })
    //         ->when($to_date, function ($query) use ($to_date) {
    //             $query->where("created_at", "<=", $to_date);
    //         })

    //         ->get();

    //     // $collected = $rr_transaction
    //     //     ->map(function ($item) {
    //     //         return (new IntegrationGLResource($item))->toArray(request());
    //     //     })
    //     //     ->flatten(1);

    //     // $collected = IntegrationGLResource::collection($rr_transaction);

    //     // $collected = IntegrationGLResource::collection(
    //     //     $rr_transaction
    //     // )->toArray([]); // Convert to an array.

    //     // // Flatten the result using collapse.
    //     // $flattened = collect($collected)->collapse();

    //     $collected = IntegrationGLResource::collection(
    //         $rr_transaction
    //     )->toArray([]);

    //     $result = array_merge(...$collected);

    //     return $result;

    //     // $formatted = IntegrationGLResource::collection($rr_transaction);

    //     // return $formatted;
    // }
}
