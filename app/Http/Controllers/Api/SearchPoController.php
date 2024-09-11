<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\POTransaction;
use App\Http\Controllers\Controller;

class SearchPoController extends Controller
{
    public function index()
    {
        $po_transaction = POTransaction::with(
            "order",
            "approver_history",
            "log_history",
            "pr_transaction.order",
            "pr_transaction.approver_history",
            "pr_transaction.log_history"
        )
            ->withTrashed()
            ->get();

        return $po_transaction;
    }
}
