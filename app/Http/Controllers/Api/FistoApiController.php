<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use App\Models\LogHistory;
use Illuminate\Http\Request;
use App\Models\POTransaction;
use App\Models\RRTransaction;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\FistoApi\TaggedRequest;

class FistoApiController extends Controller
{
    public function index()
    {
        $rr_orders = RRTransaction::with(
            "rr_orders",
            "rr_orders.order.uom",
            "po_transaction.company",
            "po_transaction.department",
            "po_transaction.department_unit",
            "po_transaction.sub_unit",
            "po_transaction.location",
            "po_transaction.account_title",
            "po_transaction.account_title.account_type",
            "po_transaction.account_title.account_group",
            "po_transaction.account_title.account_sub_group",
            "po_transaction.account_title.financial_statement"
        )
            ->useFilters()
            ->dynamicPaginate();

        $is_empty = $rr_orders->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $rr_orders
        );
    }

    public function update(TaggedRequest $request, $id)
    {
        $fisto_id_no = $request->id_no;

        $rr_transaction = RRTransaction::with(["rr_orders", "log_history"])
            ->where("id", $id)
            ->get()
            ->first();

        if (!$rr_transaction) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $rr_orders_to_tag = $rr_transaction->rr_orders->filter(function (
            $order
        ) {
            return $order->f_tagged === 0;
        });

        if ($rr_orders_to_tag->isEmpty()) {
            return GlobalFunction::notFound(Message::NO_ITEM_FOUND);
        }

        $taggedOrders = [];
        foreach ($rr_orders_to_tag as $rr_order) {
            $rr_order->update(["f_tagged" => 1]);
            $taggedOrders[] = $rr_order;
        }

        $taggedItems = $rr_orders_to_tag
            ->map(function ($order) {
                return "Item Name: " .
                    $order->item_name .
                    ", Quantity Received: " .
                    $order->quantity_receive .
                    ", Shipment No: " .
                    $order->shipment_no;
            })
            ->implode("; ");

        $activityDescription =
            "RR Transaction ID: " .
            $id .
            " has been tagged by UID: " .
            $fisto_id_no .
            " from Fisto. Tagged Items: " .
            $taggedItems;

        LogHistory::create([
            "activity" => $activityDescription,
            "rr_id" => $id,
        ]);

        $rr_transaction->load([
            "rr_orders" => function ($query) use ($taggedOrders) {
                $query->whereIn("id", collect($taggedOrders)->pluck("id"));
            },
            "log_history" => function ($query) {
                $query->orderByDesc("id");
            },
        ]);

        return GlobalFunction::responseFunction(
            Message::RR_TAGGED_FISTO,
            $rr_transaction
        );
    }
}
