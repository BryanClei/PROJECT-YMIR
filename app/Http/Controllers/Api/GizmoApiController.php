<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\POTransaction;
use App\Http\Controllers\Controller;

class GizmoApiController extends Controller
{
    public function index()
    {
        $po_transaction = POTransaction::with("order")
            ->whereIn("status", ["For Receiving", "Approved"])
            ->whereNotNull("approved_at")
            ->get();

        return $po_transaction;
    }
}
