<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use Illuminate\Http\Request;
use App\Models\PRTransaction;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Resources\PRTransactionResource;

class PrReportController extends Controller
{
    public function show(Request $request, $id)
    {
        $single_view_report = PRTransaction::with(
            "order",
            "approver_history"
        )->find($id);

        if (!$single_view_report) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $single_view_report
        );
    }
}
