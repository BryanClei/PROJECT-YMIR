<?php

namespace App\Http\Controllers\Api;

use App\Models\RROrders;
use App\Response\Message;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Resources\IntegrationGLResource;
use Illuminate\Support\Carbon;

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
            ->get();

        $collected = IntegrationGLResource::collection($rr_transaction);

        return $collected;
    }
}
