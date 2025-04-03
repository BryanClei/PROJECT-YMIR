<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Models\JobOrderTransaction;
use App\Http\Controllers\Controller;

class JrReportController extends Controller
{
    public function show(Request $request, $id)
    {
        $single_view_report = JobOrderTransaction::with(
            "order",
            "approver_history"
        )->find($id);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $single_view_report
        );
    }
}
