<?php

namespace App\Http\Controllers\Api;

use App\Models\POItems;
use App\Models\RROrders;
use App\Response\Message;
use App\Models\LogHistory;
use Illuminate\Http\Request;
use App\Models\POTransaction;
use App\Models\PRTransaction;
use App\Models\RRTransaction;
use App\Functions\GlobalFunction;
use App\Http\Resources\PoResource;
use App\Http\Resources\RRResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\PRViewRequest;
use App\Http\Resources\RRSyncDisplay;
use App\Http\Resources\RROrdersResource;
use App\Http\Resources\PRTransactionResource;
use App\Http\Requests\AssetVladimir\UpdateRequest;
use App\Http\Requests\ReceivedReceipt\StoreRequest;
use App\Http\Resources\LogHistory\LogHistoryResource;

class RRTransactionController extends Controller
{
    public function index()
    {
        $rr_transaction = RRTransaction::with(
            "rr_orders",
            "pr_transaction.users",
            "pr_transaction"
        )
            ->orderByDesc("updated_at")
            ->useFilters()
            ->dynamicPaginate();

        if ($rr_transaction->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        RRResource::collection($rr_transaction);

        return GlobalFunction::responseFunction(
            Message::RR_DISPLAY,
            $rr_transaction
        );
    }

    public function show($id)
    {
        $rr_transaction = RRTransaction::with(
            "pr_transaction.users",
            "rr_orders",
            "po_transaction",
            "po_order"
        )
            ->where("id", $id)
            ->orderByDesc("updated_at")
            ->first();

        if (!$rr_transaction) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        new RRResource($rr_transaction);

        return GlobalFunction::responseFunction(
            Message::RR_DISPLAY,
            $rr_transaction
        );
    }

    public function store(StoreRequest $request)
    {
        $user_id = Auth()->user()->id;
        $po_id = $request->po_no;
        $po_transaction = POTransaction::where("id", $po_id)->first();
        $new_add = $request->new_or_add_to_receipt;

        $po_transaction->pr_number;
        $orders = $request->order;

        foreach ($orders as $order) {
            $itemIds[] = $order["id"];
        }

        $po_items = POItems::whereIn("id", $itemIds)
            ->get()
            ->toArray();

        $quantities = [];
        $quantities_serve = [];
        foreach ($po_items as $item) {
            $quantities[$item["id"]] = $item["quantity"];
            $quantities_serve[$item["id"]] = $item["quantity_serve"];
        }

        foreach ($orders as $index => $values) {
            $item_id = $request["order"][$index]["id"];
            $original_quantity = $quantities[$item_id] ?? 0;
            $quantity_serve = $request["order"][$index]["quantity_serve"];

            $get_quantity_serve = POItems::where("id", $item_id)
                ->get()
                ->first();

            if ($get_quantity_serve->quantity_serve <= 0) {
                if ($original_quantity < $quantity_serve) {
                    return GlobalFunction::invalid(
                        Message::QUANTITY_VALIDATION
                    );
                }
            } elseif (
                $get_quantity_serve->quantity ===
                $get_quantity_serve->quantity_serve
            ) {
                return GlobalFunction::invalid(Message::QUANTITY_VALIDATION);
            } else {
                $remaining_ondb =
                    $get_quantity_serve->quantity -
                    $get_quantity_serve->quantity_serve;
                if ($remaining_ondb < $quantity_serve) {
                    return GlobalFunction::invalid(
                        Message::QUANTITY_VALIDATION
                    );
                }
            }
        }

        $rr_transaction = new RRTransaction([
            "pr_id" => $po_transaction->pr_number,
            "po_id" => $po_transaction->po_number,
            "received_by" => $user_id,
            "tagging_id" => $request->tagging_id,
        ]);

        $rr_transaction->save();

        foreach ($orders as $index => $values) {
            $item_id = $request["order"][$index]["id"];
            $quantity_serve = $request["order"][$index]["quantity_serve"];
            $original_quantity = $quantities[$item_id] ?? 0;

            $original_quantity_serve = POItems::where("id", $item_id)
                ->get()
                ->first();

            $os = $original_quantity_serve->quantity_serve + $quantity_serve;
            $remaining = $original_quantity - $os;

            RROrders::create([
                "rr_number" => $rr_transaction->id,
                "rr_id" => $rr_transaction->id,
                "item_id" => $item_id,
                "item_code" => $request["order"][$index]["item_code"],
                "item_name" => $request["order"][$index]["item_name"],
                "quantity_receive" => $quantity_serve,
                "remaining" => $remaining,
                "shipment_no" => $request["order"][$index]["shipment_no"],
                "delivery_date" => $request["order"][$index]["delivery_date"],
                "rr_date" => $request["order"][$index]["rr_date"],
                "sync" => 0,
            ]);

            $po_item = POItems::find($item_id);
            $po_item->update([
                "quantity_serve" =>
                    $original_quantity_serve->quantity_serve +
                    $request["order"][$index]["quantity_serve"],
            ]);
        }

        $rr_collect = new RRResource($rr_transaction);

        return GlobalFunction::responseFunction(
            Message::RR_DISPLAY,
            $rr_collect
        );
    }

    public function update(Request $request, $id)
    {
        $rr_number = $id;

        $orders = $request->order;
        $rr_transaction = RRTransaction::where("id", $rr_number)->first();

        $itemIds = [];
        $rr_collect = [];

        foreach ($orders as $index => $values) {
            $rr_orders_id = $request["order"][$index]["item_id"];
            $quantity_receiving = $request["order"][$index]["quantity_serve"];
            $po_order = POItems::where("id", $rr_orders_id)
                ->get()
                ->first();
            $remaining = $po_order->quantity - $po_order->quantity_serve;

            if ($quantity_receiving > $remaining) {
                return GlobalFunction::invalid(Message::QUANTITY_VALIDATION);
            }
        }

        foreach ($orders as $index => $values) {
            $rr_orders_id = $request["order"][$index]["item_id"];

            $po_orders = POItems::where("id", $rr_orders_id)
                ->get()
                ->first();

            $remaining = $po_orders->quantity - $po_orders->quantity_serve;

            $add_previous = RROrders::create([
                "rr_number" => $rr_number,
                "rr_id" => $rr_number,
                "item_id" => $rr_orders_id,
                "item_name" => $request["order"][$index]["item_name"],
                "item_code" => $request["order"][$index]["item_code"],
                "quantity_receive" =>
                    $request["order"][$index]["quantity_serve"],
                "remaining" =>
                    $remaining - $request["order"][$index]["quantity_serve"],
                "shipment_no" => $request["order"][$index]["shipment_no"],
                "delivery_date" => $request["order"][$index]["delivery_date"],
                "rr_date" => $request["order"][$index]["rr_date"],
                "sync" => 0,
            ]);

            $po_orders->update([
                "quantity_serve" =>
                    $po_orders->quantity_serve +
                    $request["order"][$index]["quantity_serve"],
            ]);

            $rr_collect[] = new RROrdersResource($add_previous);
        }

        return GlobalFunction::responseFunction(
            Message::RR_DISPLAY,
            $rr_collect
        );
    }

    public function index_po_approved(PRViewRequest $request)
    {
        $status = $request->status;
        $po_approve = POTransaction::with(
            "order",
            "approver_history",
            "rr_transaction",
            "rr_transaction.rr_orders"
        )
            ->orderByDesc("updated_at")
            ->useFilters()
            ->dynamicPaginate();

        if ($po_approve->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        PoResource::collection($po_approve);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $po_approve
        );
    }

    public function view_po_approved($id)
    {
        $po_approve = POTransaction::with("order", "rr_transaction")
            ->where("id", $id)
            ->first();

        if (!$po_approve) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $po_collect = new PoResource($po_approve);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $po_collect
        );
    }

    public function asset_vladimir()
    {
        $purchase_request = POTransaction::with([
            "rr_transaction.rr_orders",
            "order",
        ])
            ->where("module_name", "Assets")
            ->whereHas("rr_transaction")
            ->orderByDesc("updated_at")
            ->useFilters()
            ->dynamicPaginate();

        $is_empty = $purchase_request->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            RRSyncDisplay::collection($purchase_request)
        );
    }

    public function asset_sync(UpdateRequest $request)
    {
        $request_orders = $request->rr_number;
        $updated_records = [];

        foreach ($request_orders as $rr_number) {
            $rr_orders = RROrders::where("rr_number", $rr_number)->get();

            foreach ($rr_orders as $order) {
                $order->sync = true;
                $order->save();

                $updated_records[] = [
                    "rr_number" => $order->rr_number,
                    "sync" => $order->sync,
                ];
            }
        }

        return GlobalFunction::save(Message::ASSET_UPDATE, $updated_records);
    }

    public function logs()
    {
        $log_history = LogHistory::useFilters()->dynamicPaginate();

        if ($log_history->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        LogHistoryResource::collection($log_history);

        return GlobalFunction::responseFunction(
            Message::DISPLAY_LOG_HISTORY,
            $log_history
        );
    }

    public function report_pr(Request $request)
    {
        $purchase_order = PRTransaction::with("order", "approver_history")
            ->where("status", "Approved")
            ->whereNotNull("approved_at")
            ->useFilters()
            ->dynamicPaginate();

        $is_empty = $purchase_order->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        PRTransactionResource::collection($purchase_order);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $purchase_order
        );
    }
}
