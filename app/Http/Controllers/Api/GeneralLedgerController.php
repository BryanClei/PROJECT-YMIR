<?php

namespace App\Http\Controllers\Api;

use App\Models\RROrders;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class GeneralLedgerController extends Controller
{
    public function index()
    {
        $rr_transaction = RROrders::with(
            "rr_transaction",
            "po_transaction"
        )->get();

        return $rr_transaction;
    }
}
