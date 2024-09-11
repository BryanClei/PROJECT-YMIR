<?php

namespace App\Http\Controllers\Api;

use App\Models\Items;
use App\Models\POItems;
use App\Models\PRItems;
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
use App\Http\Requests\PO\PORequest;

class RRTransactionController extends Controller
{
    public function index(Request $request)
    {
        $rr_transaction = RRTransaction::with([
            "rr_orders" => function ($query) {
                $query->withTrashed();
            },
            "pr_transaction.users",
            "pr_transaction",
        ])

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
            "pr_transaction.order",
            "rr_orders",
            "po_transaction",
            "po_order",
            "po_order.category"
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

        $po_transaction->module_name;

        if ($po_transaction->module_name == "Asset") {
            $pr_id_exists = PRTransaction::where(
                "pr_number",
                $request->pr_no
            )->exists();
        } else {
            $pr_id_exists = PRTransaction::where(
                "id",
                $request->pr_no
            )->exists();
        }

        if (!$pr_id_exists) {
            return GlobalFunction::invalid(Message::NOT_FOUND);
        }

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

        $current_year = date("Y");
        $latest_rr = RRTransaction::where(
            "rr_year_number_id",
            "like",
            $current_year . "-RR-%"
        )
            ->orderByRaw(
                "CAST(SUBSTRING_INDEX(rr_year_number_id, '-', -1) AS UNSIGNED) DESC"
            )
            ->first();

        $new_number = $latest_rr
            ? (int) explode("-", $latest_rr->rr_year_number_id)[2] + 1
            : 1;

        $rr_year_number_id =
            $current_year . "-RR-" . str_pad($new_number, 3, "0", STR_PAD_LEFT);

        $rr_transaction = new RRTransaction([
            "rr_year_number_id" => $rr_year_number_id,
            "pr_id" => $po_transaction->pr_number,
            "po_id" => $po_transaction->po_number,
            "received_by" => $user_id,
            "tagging_id" => $request->tagging_id,
        ]);

        $rr_transaction->save();

        $itemDetails = [];
        $itemDetails = [];
        foreach ($orders as $index => $values) {
            $item_id = $request["order"][$index]["id"];
            $quantity_serve = $request["order"][$index]["quantity_serve"];
            $original_quantity = $quantities[$item_id] ?? 0;
            $remaining =
                $original_quantity -
                ($quantities_serve[$item_id] + $quantity_serve);

            $itemDetails[] = [
                "item_name" => $request["order"][$index]["item_name"],
                "quantity_receive" => $quantity_serve,
                "remaining" => $remaining,
                "date" => $request["order"][$index]["delivery_date"],
            ];
        }

        $itemList = [];
        foreach ($itemDetails as $item) {
            $itemList[] = "{$item["item_name"]} (Received: {$item["quantity_receive"]}, Remaining: {$item["remaining"]}, Date: {$item["date"]})";
        }

        $activityDescription =
            "Received Receipt ID:" .
            $rr_transaction->id .
            " has been created by UID: " .
            $user_id .
            ". Items received: " .
            implode(", ", $itemList);

        LogHistory::create([
            "activity" => $activityDescription,
            "rr_id" => $rr_transaction->id,
            "action_by" => $user_id,
        ]);

        foreach ($orders as $index => $values) {
            $attachments = $request["order"][$index]["attachment"];
            $filenames = [];
            if (!empty($attachments)) {
                foreach ($attachments as $fileIndex => $file) {
                    $originalFilename = basename($file);
                    $info = pathinfo($originalFilename);
                    $filenameOnly = $info["filename"];
                    $extension = $info["extension"];
                    $filename = "{$filenameOnly}_rr_id_{$rr_transaction->id}_item_{$index}_file_{$fileIndex}.{$extension}";
                    $filenames[] = $filename;
                }
            }

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
                "attachment" => json_encode($filenames),
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

        return GlobalFunction::responseFunction(Message::RR_SAVE, $rr_collect);
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
                "attachment" => $request["order"][$index]["attachment"],
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
            Message::RR_UPDATE,
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

    public function asset_vladimir($id)
    {
        $purchase_request = POTransaction::with([
            "rr_transaction.rr_orders",
            "order",
        ])
            ->where("module_name", "Asset")
            ->whereHas("rr_transaction", function ($query) use ($id) {
                $query->where("id", $id);
            })
            ->orderByDesc("updated_at")
            ->useFilters()
            ->dynamicPaginate();

        $is_empty = $purchase_request->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::RR_DISPLAY,
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

    public function report_pr(PRViewRequest $request)
    {
        $purchase_order = PRTransaction::with(
            "users",
            "order",
            "approver_history"
        )
            ->useFilters()
            ->dynamicPaginate();

        $is_empty = $purchase_order->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $purchase_order
        );
    }

    public function report_po(Request $request)
    {
        $from = $request->input("from");
        $to = $request->input("to");
        $po_items = POItems::with(
            "uom",
            "pr_item",
            "po_transaction.users",
            "po_transaction.pr_transaction",
            "po_transaction",
            "po_transaction.rr_transaction",
            "po_transaction.rr_transaction.rr_orders"
        )
            ->useFilters()
            ->dynamicPaginate();

        $is_empty = $po_items->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $po_items
        );
    }

    public function report_rr()
    {
        $rr_orders = RROrders::with(
            "pr_items",
            "pr_items.uom",
            "order",
            "rr_transaction",
            "rr_transaction.pr_transaction",
            "rr_transaction.pr_transaction.users",
            "rr_transaction.po_transaction"
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

    public function cancel_rr($id)
    {
        $rr_transaction = RRTransaction::where("id", $id)
            ->with("rr_orders", "po_order")
            ->get()
            ->first();

        if (!$rr_transaction) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $po_orders = $rr_transaction->rr_orders->pluck("item_id")->toArray();

        $po_items = POItems::whereIn("id", $po_orders)->get();

        foreach ($rr_transaction->rr_orders as $rr_order) {
            $po_item = $po_items->where("id", $rr_order->item_id)->first();

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

    public function rr_badge()
    {
        $user_id = Auth()->user()->id;
        $for_receiving = POTransaction::with(
            "order",
            "approver_history",
            "rr_transaction",
            "rr_transaction.rr_orders"
        )

            ->with([
                "order" => function ($query) {
                    $query->whereColumn("quantity", "<>", "quantity_serve");
                },
            ])
            ->where("status", "For Receiving")
            ->whereNull("cancelled_at")
            ->whereNull("voided_at")
            ->whereHas("approver_history", function ($query) {
                $query->whereNotNull("approved_at");
            })
            ->whereHas("order", function ($query) {
                $query->whereColumn("quantity", "<>", "quantity_serve");
            })
            ->count();

        $for_receivin_user = POTransaction::with(
            "order",
            "approver_history",
            "rr_transaction",
            "rr_transaction.rr_orders"
        )
            ->with([
                "order" => function ($query) {
                    $query->whereColumn("quantity", "<>", "quantity_serve");
                },
            ])
            ->where("user_id", $user_id)
            ->where("status", "For Receiving")
            ->whereNull("cancelled_at")
            ->whereNull("voided_at")
            ->whereHas("approver_history", function ($query) {
                $query->whereNotNull("approved_at");
            })
            ->whereHas("order", function ($query) {
                $query->whereColumn("quantity", "<>", "quantity_serve");
            })
            ->count();

        $result = [
            "for_receiving" => $for_receiving,
            "for_receiving_user" => $for_receivin_user,
        ];

        return GlobalFunction::responseFunction(
            Message::DISPLAY_COUNT,
            $result
        );
    }
}
