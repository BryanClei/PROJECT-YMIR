<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use App\Models\LogHistory;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Models\PurchaseAssistant;
use App\Http\Controllers\Controller;

class PAReturnTaggedController extends Controller
{
    public function update(Request $request, $id)
    {
        $user_id = Auth()->user()->id;

        $transaction = PurchaseAssistant::with([
            "order" => function ($query) {
                $query->whereNull("po_at");
            },
        ])->find($id);

        if (!$transaction) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $transaction
            ->order()
            ->whereNull("po_at")
            ->whereNotNull("buyer_id")
            ->update([
                "buyer_id" => null,
                "buyer_name" => null,
                "tagged_buyer" => null,
            ]);

        $activityDescription =
            "Purchase request ID: " .
            $transaction->id .
            " has been removed tagging by UID: " .
            $user_id;

        LogHistory::create([
            "activity" => $activityDescription,
            "pr_id" => $transaction->id,
            "action_by" => $user_id,
        ]);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_UPDATE,
            $transaction
        );
    }
}
