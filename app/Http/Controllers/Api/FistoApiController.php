<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\POTransaction;
use App\Http\Controllers\Controller;

class FistoApiController extends Controller
{
    public function index()
    {
        $po_transaction_with_rr = POTransaction::whereHas("rr_transaction")
            ->with(["order", "rr_transaction.rr_orders"])
            ->get();

        return $po_transaction_with_rr;
    }
}
