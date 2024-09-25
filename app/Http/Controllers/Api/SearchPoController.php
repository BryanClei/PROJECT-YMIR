<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\POTransaction;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;

class SearchPoController extends Controller
{
    public function index()
    {
        $po_transaction = POTransaction::with(
            "order",
            "approver_history",
            "log_history.users",
            "pr_transaction",
            "pr_transaction.order",
            "pr_transaction.approver_history",
            "pr_transaction.log_history.users",
            "rr_transaction.rr_orders",
            "rr_transaction.log_history.users"
        )
            ->withTrashed()
            ->useFilters()
            ->dynamicPaginate();

        if ($po_transaction->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return $po_transaction;
    }
}
