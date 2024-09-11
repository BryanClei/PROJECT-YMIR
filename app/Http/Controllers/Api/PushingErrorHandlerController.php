<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;

class PushingErrorHandlerController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth()->user()->id;
        $sync = $request->sync;
        $push = $request->push;
        $rr_id = $request->rr_id;

        $sync_error = $sync
            ? ($error = "Sync Error -> " . $request->response)
            : "False";
        $push_error = $push
            ? ($error = "Push Error -> " . $request->response)
            : "False";

        $activityDescription =
            "Received Receipt ID:" . $rr_id . " Error: " . $error;

        $log = LogHistory::create([
            "activity" => $activityDescription,
            "rr_id" => $rr_id,
            "action_by" => $user,
        ]);

        $log = $log->fresh();

        return GlobalFunction::save(Message::LOG_SUCCESSFULLY, $log);
    }
}
