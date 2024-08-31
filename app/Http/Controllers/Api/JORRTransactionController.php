<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use App\Models\JoPoOrders;
use App\Models\JORROrders;
use Illuminate\Http\Request;
use App\Models\JOPOTransaction;
use App\Models\JORRTransaction;
use App\Http\Requests\JODisplay;
use App\Functions\GlobalFunction;
use App\Http\Requests\PO\PORequest;
use App\Models\JobOrderTransaction;
use App\Http\Controllers\Controller;
use App\Http\Requests\PRViewRequest;
use App\Http\Resources\JoPoResource;
use App\Http\Resources\JORRResource;
use App\Http\Resources\JobOrderResource;
use App\Http\Resources\JORROrderResource;
use App\Http\Requests\JoRROrder\StoreRequest;

class JORRTransactionController extends Controller
{
    public function index(JODisplay $request)
    {
        $display = JORRTransaction::with("rr_orders", "jo_po_transactions")
            ->withTrashed()
            ->useFilters()
            ->dynamicPaginate();

        if ($display->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        new JORRResource($display);
        return GlobalFunction::responseFunction(Message::RR_DISPLAY, $display);
    }

    public function show(Request $request, $id)
    {
        $jo_rr_transaction = JORRTransaction::with(
            "rr_orders",
            "jo_po_transactions",
            "jo_po_transactions.order"
        )
            ->where("id", $id)
            ->orderByDesc("updated_at")
            ->first();

        if (!$jo_rr_transaction) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $jo_po_collect = new JORRResource($jo_rr_transaction);

        return GlobalFunction::responseFunction(
            Message::RR_DISPLAY,
            $jo_po_collect
        );
    }

    public function view_approve_jo_po(PRViewRequest $request)
    {
        $user_id = Auth()->user()->id;
        $status = $request->status;
        $job_order_request = JOPOTransaction::with(
            "jo_po_orders",
            "jo_approver_history",
            "jo_transaction.users"
        )
            ->orderByDesc("updated_at")
            ->useFilters()
            ->dynamicPaginate();

        $is_empty = $job_order_request->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        JoPoResource::collection($job_order_request);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $job_order_request
        );
    }

    public function view_single_approve_jo_po(Request $request, $id)
    {
        $job_order_request = JOPOTransaction::where("id", $id)
            ->with("jo_po_orders", "jo_approver_history", "jo_rr_transaction")
            ->orderByDesc("updated_at")
            ->first();

        $is_empty = !$job_order_request;

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $collect = new JoPoResource($job_order_request);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $collect
        );
    }

    public function store(StoreRequest $request)
    {
        $user_id = Auth()->user()->id;
        $po_id = $request->jo_po;
        $jo_po_transaction = JOPOTransaction::where("id", $po_id)
            ->get()
            ->first();

        $orders = $request->rr_order;

        foreach ($orders as $index => $values) {
            $itemIds = $request["rr_order"][$index]["jo_item_id"];
            $quantity_serve = $request["rr_order"][$index]["quantity_serve"];

            $jo_order = JoPoOrders::where("id", $itemIds)
                ->get()
                ->first();

            if ($jo_order->quantity_serve <= 0) {
                if ($jo_order->quantity < $quantity_serve) {
                    return GlobalFunction::invalid(
                        Message::QUANTITY_VALIDATION
                    );
                }
            } elseif ($jo_order->quantity === $jo_order->quantity_serve) {
                return GlobalFunction::invalid(Message::QUANTITY_VALIDATION);
            } else {
                $remaining_ondb =
                    $jo_order->quantity - $jo_order->quantity_serve;
                if ($remaining_ondb < $quantity_serve) {
                    return GlobalFunction::invalid(
                        Message::QUANTITY_VALIDATION
                    );
                }
            }
        }

        $jo_rr_transaction = new JORRTransaction([
            "jo_po_id" => $jo_po_transaction->po_number,
            "jo_id" => $jo_po_transaction->jo_number,
            "received_by" => $user_id,
            "tagging_id" => $request->tagging_id,
        ]);

        $jo_rr_transaction->save();

        foreach ($orders as $index => $values) {
            $itemIds = $request["rr_order"][$index]["jo_item_id"];

            $jo_order = JoPoOrders::where("id", $itemIds)
                ->get()
                ->first();

            $original_quantity = $jo_order->quantity;
            $original_quantity_serve =
                $jo_order->quantity_serve +
                $request["rr_order"][$index]["quantity_serve"];
            $remaining_quantity = $original_quantity - $original_quantity_serve;

            JORROrders::create([
                "jo_rr_number" => $jo_rr_transaction->id,
                "jo_rr_id" => $jo_rr_transaction->id,
                "jo_item_id" => $jo_order->id,
                "quantity_receive" =>
                    $request["rr_order"][$index]["quantity_serve"],
                "remaining" => $remaining_quantity,
                "shipment_no" => $request["rr_order"][$index]["shipment_no"],
                "delivery_date" =>
                    $request["rr_order"][$index]["delivery_date"],
                "rr_date" => $request["rr_order"][$index]["rr_date"],
            ]);

            JoPoOrders::where("id", $itemIds)->update([
                "quantity_serve" => $original_quantity_serve,
            ]);
        }

        $rr_collect = JORRResource::collection(collect([$jo_rr_transaction]));
        // $rr_collect = new JORRResource($jo_rr_transaction);
        return GlobalFunction::responseFunction(
            Message::RR_DISPLAY,
            $rr_collect
        );
    }

    public function update(Request $request, $id)
    {
        $rr_number = $id;

        $orders = $request->rr_order;
        $jo_rr_transaction = JORRTransaction::where("id", $rr_number)->first();

        $itemIds = [];
        $rr_collect = [];

        foreach ($orders as $index => $values) {
            $rr_orders_id = $request["rr_order"][$index]["jo_item_id"];
            $quantity_receiving =
                $request["rr_order"][$index]["quantity_serve"];
            $po_order = JoPoOrders::where("id", $rr_orders_id)
                ->get()
                ->first();
            $remaining = $po_order->quantity - $po_order->quantity_serve;

            if ($quantity_receiving > $remaining) {
                return GlobalFunction::invalid(Message::QUANTITY_VALIDATION);
            }
        }

        foreach ($orders as $index => $values) {
            $rr_orders_id = $request["rr_order"][$index]["jo_item_id"];

            $jo_order = JoPoOrders::where("id", $rr_orders_id)
                ->get()
                ->first();

            $original_quantity = $jo_order->quantity;
            $original_quantity_serve =
                $jo_order->quantity_serve +
                $request["rr_order"][$index]["quantity_serve"];
            $remaining_quantity = $original_quantity - $original_quantity_serve;

            $add_previous = JORROrders::create([
                "jo_rr_number" => $rr_number,
                "jo_rr_id" => $rr_number,
                "jo_item_id" => $rr_orders_id,
                "quantity_receive" =>
                    $request["rr_order"][$index]["quantity_serve"],
                "remaining" => $remaining_quantity,
                "shipment_no" => $request["rr_order"][$index]["shipment_no"],
                "delivery_date" =>
                    $request["rr_order"][$index]["delivery_date"],
                "rr_date" => $request["rr_order"][$index]["rr_date"],
            ]);

            $created_orders[] = $add_previous;

            JoPoOrders::where("id", $rr_orders_id)->update([
                "quantity_serve" =>
                    $jo_order->quantity_serve +
                    $request["rr_order"][$index]["quantity_serve"],
            ]);
        }

        $rr_collect = JORROrderResource::collection($created_orders);

        return GlobalFunction::responseFunction(
            Message::RR_DISPLAY,
            $rr_collect
        );
    }

    public function cancel_jo_rr($id)
    {
        $rr_transaction = JORRTransaction::where("id", $id)
            ->with("rr_orders", "jo_po_order")
            ->first();

        if (!$rr_transaction) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $po_orders = $rr_transaction->rr_orders->pluck("jo_item_id")->toArray();

        $po_items = JoPoOrders::whereIn("id", $po_orders)->get();

        foreach ($rr_transaction->rr_orders as $rr_order) {
            $po_item = $po_items->where("id", $rr_order->jo_item_id)->first();

            if ($po_item) {
                $po_item->quantity_serve -= $rr_order->quantity_receive;
                $po_item->save();
            }

            $rr_order->delete();
        }

        $cancelled_rr_transaction = $rr_transaction;

        $rr_transaction->delete();

        return GlobalFunction::responseFunction(
            Message::RR_CANCELLATION,
            $cancelled_rr_transaction
        );
    }

    public function reason(PORequest $request, $id)
    {
        $request->reason;

        $rr_transaction = JORRTransaction::where("id", $id)->first();

        if (!$rr_transaction) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $rr_transaction->update([
            "reason" => $request->reason,
        ]);

        return GlobalFunction::responseFunction(
            Message::RR_CANCELLATION,
            $rr_transaction
        );
    }

    public function report_jo()
    {
        $jo_transaction = JobOrderTransaction::with(
            "users",
            "order",
            "approver_history"
        )->get();

        if ($jo_transaction->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $jo_transaction
        );
    }

    public function report_jo_po()
    {
        $jo_order = JoPoOrders::with(
            "uom",
            "transaction.users",
            "transaction.jo_po_transaction"
        )->get();

        if ($jo_order->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $jo_order
        );
    }
}
