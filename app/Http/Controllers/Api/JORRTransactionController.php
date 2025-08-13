<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\JobItems;
use App\Response\Message;
use App\Models\JoPoOrders;
use App\Models\JORROrders;
use App\Models\LogHistory;
use Illuminate\Http\Request;
use App\Models\JOPOTransaction;
use App\Models\JORRTransaction;
use App\Http\Requests\JODisplay;
use App\Functions\GlobalFunction;
use App\Helpers\RRHelperFunctions;
use App\Http\Requests\JORRDisplay;
use App\Http\Requests\PO\PORequest;
use App\Models\JobOrderTransaction;
use App\Http\Controllers\Controller;
use App\Http\Requests\PRViewRequest;
use App\Http\Resources\JoPoResource;
use App\Http\Resources\JORRResource;
use App\Http\Requests\JORRTodayDisplay;
use App\Http\Resources\JobOrderResource;
use App\Http\Resources\JORROrderResource;
use App\Http\Requests\JoRROrder\StoreRequest;
use App\Http\Requests\ReceivedReceipt\CancelRequest;
use App\Http\Requests\ReceivedReceipt\MultipleRequest;

class JORRTransactionController extends Controller
{
    public function index(JODisplay $request)
    {
        $display = JORRTransaction::with([
            "rr_orders" => function ($query) {
                $query->withTrashed();
            },
            "jo_po_transactions" => function ($query) {
                $query->withTrashed();
            },
            "jr_order",
            "log_history",
        ])
            ->orderByDesc("created_at")
            ->withTrashed()
            ->useFilters()
            ->dynamicPaginate();

        if ($display->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        JORRResource::collection($display);
        return GlobalFunction::responseFunction(Message::RR_DISPLAY, $display);
    }

    public function show(Request $request, $id)
    {
        $jo_rr_transaction = JORRTransaction::with(
            "rr_orders.jo_po_transaction.jo_po_orders.uom",
            "jo_po_transactions.order.uom",
            "log_history"
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
            "jo_po_orders.uom",
            "jo_approver_history",
            "jo_transaction.users"
        )
            ->orderByDesc("created_at")
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
            ->with([
                "jo_po_orders",
                "jo_approver_history",
                "jo_rr_transaction" => function ($query) {
                    $query->withTrashed()->with("rr_orders");
                },
            ])
            ->orderByDesc("updated_at")
            ->first();

        $is_empty = !$job_order_request;

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $jo = new JoPoResource($job_order_request);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $jo
        );
    }

    public function store(StoreRequest $request)
    {
        $user_id = Auth()->user()->id;
        // $po_id = $request->jo_po_id;
        // $jo_po_transaction = JOPOTransaction::where("id", $po_id)->first();

        $po_numbers = array_unique(
            array_column($request->rr_order, "jo_po_id")
        );

        $jo_po_transaction = JOPOTransaction::whereIn(
            "po_number",
            $po_numbers
        )->first();

        $orders = $request->rr_order;

        foreach ($orders as $index => $values) {
            $itemIds = $request["rr_order"][$index]["jo_item_id"];
            $quantity_serve = $request["rr_order"][$index]["quantity_serve"];

            $jo_order = JoPoOrders::where("id", $itemIds)->first();

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

        $current_year = date("Y");
        $latest_rr = JORRTransaction::withTrashed()
            ->where("jo_rr_year_number_id", "like", $current_year . "-JR-RR-%")
            ->orderByRaw(
                "CAST(SUBSTRING_INDEX(jo_rr_year_number_id, 'RR-', -1) AS UNSIGNED) DESC"
            )
            ->first();

        $new_number = $latest_rr
            ? (int) explode("-", $latest_rr->jo_rr_year_number_id)[3] + 1
            : 1;
        $jo_rr_year_number_id =
            $current_year .
            "-JR-RR-" .
            str_pad($new_number, 3, "0", STR_PAD_LEFT);

        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i:s");

        $jo_rr_transaction = new JORRTransaction([
            "jo_rr_year_number_id" => $jo_rr_year_number_id,
            "jo_po_id" => $jo_po_transaction->po_number,
            "jo_id" => $jo_po_transaction->jo_number,
            "received_by" => $user_id,
            "tagging_id" => $request->tagging_id,
            "transaction_date" => $request->transaction_date,
            "attachment" => $request->attachment,
        ]);

        $jo_rr_transaction->save();

        $itemDetails = [];

        foreach ($orders as $index => $values) {
            $itemIds = $request["rr_order"][$index]["jo_item_id"];

            $jo_order = JoPoOrders::where("id", $itemIds)->first();

            $original_quantity = $jo_order->quantity;
            $original_quantity_serve =
                $jo_order->quantity_serve +
                $request["rr_order"][$index]["quantity_serve"];
            $remaining_quantity = $original_quantity - $original_quantity_serve;

            $createdOrder = JORROrders::create([
                "jo_rr_number" => $jo_rr_transaction->id,
                "jo_rr_id" => $jo_rr_transaction->id,
                "jo_po_id" => $request["rr_order"][$index]["jo_po_id"],
                "jo_id" => $request["rr_order"][$index]["jo_id"],
                "jo_item_id" => $jo_order->id,
                "description" => $request["rr_order"][$index]["description"],
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

            $itemDetails[] = [
                "description" => $jo_order->description,
                "quantity_receive" =>
                    $request["rr_order"][$index]["quantity_serve"],
                "remaining" => $remaining_quantity,
                "date" => $request["rr_order"][$index]["rr_date"],
            ];
        }

        $itemList = array_map(function ($item) {
            return "{$item["description"]} (Received: {$item["quantity_receive"]}, Remaining: {$item["remaining"]}, Date Received: {$item["date"]})";
        }, $itemDetails);

        $activityDescription =
            "Received Receipt ID" .
            ": {$jo_rr_transaction->id} has been received by UID: {$user_id}. Items received: " .
            implode(", ", $itemList);

        LogHistory::create([
            "activity" => $activityDescription,
            "jo_rr_id" => $jo_rr_transaction->id,
            "action_by" => $user_id,
        ]);

        $rr_collect = JORRResource::collection(collect([$jo_rr_transaction]));

        return GlobalFunction::responseFunction(
            Message::RR_DISPLAY,
            $rr_collect
        );
    }

    public function jo_rr_multiple(MultipleRequest $request, $id)
    {
        $rr_number = $id;
        $created_orders = [];
        $user_id = Auth()->user()->id;

        $jo_rr_transaction = JORRTransaction::where("id", $rr_number)->first();

        if (!$jo_rr_transaction) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        foreach ($request->rr_order as $index => $order) {
            $po_order = JoPoOrders::where("id", $order["jo_item_id"])
                ->where("jo_po_id", $order["jo_po_id"])
                ->first();

            if (!$po_order) {
                return GlobalFunction::invalid("Invalid PO item combination");
            }

            $remaining = $po_order->quantity - $po_order->quantity_serve;

            if ($order["quantity_serve"] > $remaining) {
                return GlobalFunction::invalid(
                    Message::QUANTITY_VALIDATION .
                        " for PO item: " .
                        $order["jo_item_id"]
                );
            }
        }

        $itemDetails = [];

        foreach ($request->rr_order as $index => $order) {
            $po_order = JoPoOrders::where("id", $order["jo_item_id"])
                ->where("jo_po_id", $order["jo_po_id"])
                ->first();

            $original_quantity_serve =
                $po_order->quantity_serve + $order["quantity_serve"];
            $remaining_quantity =
                $po_order->quantity - $original_quantity_serve;

            $add_previous = JORROrders::create([
                "jo_rr_number" => $jo_rr_transaction->id,
                "jo_rr_id" => $jo_rr_transaction->id,
                "jo_po_id" => $po_order->jo_po_id,
                "jo_id" => $po_order->jo_transaction_id,
                "jo_item_id" => $order["jo_item_id"],
                "description" => $order["description"],
                "quantity_receive" => $order["quantity_serve"],
                "remaining" => $remaining_quantity,
                "shipment_no" => $order["shipment_no"],
                "delivery_date" => $order["delivery_date"],
                "rr_date" => $order["rr_date"],
                "attachment" => $order["attachment"],
            ]);

            $itemDetails[] = [
                "item_name" => $order["description"],
                "quantity_receive" => $order["quantity_serve"],
                "remaining" => $remaining_quantity,
                "po_no" => $order["jo_po_id"],
                "date" => $order["rr_date"],
            ];

            $created_orders[] = $add_previous;

            JoPoOrders::where("id", $order["jo_item_id"])->update([
                "quantity_serve" => $original_quantity_serve,
            ]);
        }

        $itemList = array_map(function ($item) {
            return "{$item["item_name"]} (Received: {$item["quantity_receive"]}, Remaining: {$item["remaining"]}, PO: {$item["po_no"]}, Date Received: {$item["date"]})";
        }, $itemDetails);

        $activityDescription =
            "Received Receipt ID: {$jo_rr_transaction->id} has been received by UID: {$user_id}. Items received: " .
            implode(", ", $itemList);

        LogHistory::create([
            "activity" => $activityDescription,
            "jo_rr_id" => $jo_rr_transaction->id,
            "action_by" => $user_id,
        ]);

        $rr_collect = JORROrderResource::collection($created_orders);

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
                "jo_rr_number" => $request["rr_order"][$index]["jo_rr_number"],
                "jo_rr_id" => $request["rr_order"][$index]["jo_rr_id"],
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

    public function cancel_jo_rr(CancelRequest $request, $id)
    {
        $user = Auth()->user()->id;
        $reason = $request->reason;

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

        $activityDescription = "Received Receipt ID: {$rr_transaction->id} has been cancelled by UID: {$user}. Reason: {$reason}.";

        LogHistory::create([
            "activity" => $activityDescription,
            "jo_rr_id" => $rr_transaction->id,
            "action_by" => $user,
        ]);

        $cancelled_rr_transaction = $rr_transaction;

        $rr_transaction->update(["reason" => $reason]);
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

    public function report_jo_rr(Request $request)
    {
        $user_id = Auth()->user()->id;
        $type = $request->type;
        $from_po_date = $request->from;
        $to_po_date = $request->to;
        $display = JORROrders::with([
            "order",
            "order.uom",
            "jo_rr_transaction",
            "jo_po_transaction" => function ($query) {
                $query->withTrashed();
            },
            "jo_po_transaction.jo_transaction" => function ($query) {
                $query->withTrashed();
            },
            "jo_po_transaction.supplier",
            "jo_po_transaction.jo_transaction.users",
            "jo_po_transaction.jo_transaction.approver_history",
            "jo_po_transaction.company",
            "jo_po_transaction.department",
            "jo_po_transaction.department_unit",
            "jo_po_transaction.sub_unit",
            "jo_po_transaction.location",
            "jo_po_transaction.account_title",
            "jo_po_transaction.account_title.account_type",
            "jo_po_transaction.account_title.account_group",
            "jo_po_transaction.account_title.account_sub_group",
            "jo_po_transaction.account_title.financial_statement",
            "jo_po_transaction.jo_approver_history" => function ($query) {
                $query->with("user");
            },
        ])->whereNull("deleted_at");

        if ($type === "for_user") {
            $display->whereHas("jo_po_transaction", function ($q) use (
                $user_id
            ) {
                $q->where("user_id", $user_id);
            });
        }
        if ($from_po_date && $to_po_date) {
            $display->whereHas("jo_po_transaction", function ($q) use (
                $from_po_date,
                $to_po_date
            ) {
                $q->whereBetween("created_at", [$from_po_date, $to_po_date]);
            });
        } elseif ($from_po_date) {
            $display->whereHas("jo_po_transaction", function ($q) use (
                $from_po_date
            ) {
                $q->whereDate("created_at", ">=", $from_po_date);
            });
        } elseif ($to_po_date) {
            $display->whereHas("jo_po_transaction", function ($q) use (
                $to_po_date
            ) {
                $q->whereDate("created_at", "<=", $to_po_date);
            });
        }

        $jo_rr_orders = $display->useFilters()->dynamicPaginate();

        if ($jo_rr_orders->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        foreach ($jo_rr_orders as $item) {
            if (isset($item->jo_po_transaction->jo_approver_history)) {
                foreach (
                    $item->jo_po_transaction->jo_approver_history
                    as $history
                ) {
                    if (
                        isset($history->user) &&
                        $history->user->position === "CEO" &&
                        $history->user->position === "CHIEF EXECUTIVE OFFICER"
                    ) {
                        $history->approver_type = "CEO";
                    } elseif ($history->approver_name === "ROBERT LO") {
                        $history->approver_type = "CEO";
                    }
                }
            }
        }

        return GlobalFunction::responseFunction(
            Message::RR_DISPLAY,
            $jo_rr_orders
        );
    }

    public function report_jo()
    {
        $jo_transaction = JobOrderTransaction::with(
            "users",
            "order",
            "approver_history"
        )
            ->useFilters()
            ->dynamicPaginate();

        if ($jo_transaction->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $jo_transaction
        );
    }

    public function report_jo_po(Request $request)
    {
        $jo_order = JoPoOrders::with(
            "uom",
            "jr_orders",
            "jo_po_transaction.users",
            "jo_po_transaction.jo_approver_history",
            "jo_po_transaction.jo_transaction",
            "jo_po_transaction.jo_rr_transaction.rr_orders"
        )
            ->useFilters()
            ->dynamicPaginate();

        if ($jo_order->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $jo_order
        );
    }

    public function cancel_jo_rr_display(JORRDisplay $request)
    {
        $display = JORRTransaction::whereNotNull("deleted_at")
            ->with("rr_orders", "jo_po_transactions", "log_history")
            ->withTrashed()
            ->useFilters()
            ->dynamicPaginate();

        if ($display->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        JORRResource::collection($display);
        return GlobalFunction::responseFunction(Message::RR_DISPLAY, $display);
    }

    public function cancel_jo_rr_display_show($id)
    {
        $display = JORRTransaction::where("id", $id)
            ->whereNotNull("deleted_at")
            ->with("rr_orders", "jo_po_transactions", "log_history")
            ->withTrashed()
            ->useFilters()
            ->dynamicPaginate();

        if ($display->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        JORRResource::collection($display);
        return GlobalFunction::responseFunction(Message::RR_DISPLAY, $display);
    }

    public function jo_rr_today(JORRTodayDisplay $request)
    {
        $status = $request->status;
        $supplier = $request->supplier;
        $date_today = Carbon::now()->timeZone("Asia/Manila");

        $rr_transaction = JORRTransaction::with("rr_orders")
            ->when($status === "rr_today", function ($query) use (
                $supplier,
                $date_today
            ) {
                $query
                    ->whereHas("rr_orders.jo_po_transaction", function (
                        $query
                    ) use ($supplier, $date_today) {
                        $query->where("supplier_id", $supplier);
                    })
                    ->whereDate("created_at", $date_today);
            })
            ->useFilters()
            ->dynamicPaginate();

        if ($rr_transaction->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        JORRResource::collection($rr_transaction);

        return GlobalFunction::responseFunction(
            Message::RR_DISPLAY,
            $rr_transaction
        );
    }

    public function report_jr(PRViewRequest $request)
    {
        $status = $request->status;

        $jr_orders = JobItems::with(
            "jo_po_orders.jo_po_transaction",
            "jo_po_orders.rr_orders.jo_rr_transaction",
            "uom",
            "transaction.approver_history",
            "transaction.users"
        )
            ->orderBy("updated_at", "desc")
            ->useFilters()
            ->dynamicPaginate();

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $jr_orders
        );

        // $job_order_request = JobOrderTransaction::with(
        //     "order.uom",
        //     "order.assets",
        //     "approver_history",
        //     "log_history.users",
        //     "jo_po_transaction",
        //     "jo_po_transaction.jo_approver_history",
        //     "jo_po_transaction.jo_po_orders.rr_orders.jo_rr_transaction"
        // )
        //     ->orderBy("rush", "desc")
        //     ->orderBy("updated_at", "desc")
        //     ->useFilters()
        //     ->dynamicPaginate();

        // if ($job_order_request->isEmpty()) {
        //     return GlobalFunction::notFound(Message::NOT_FOUND);
        // }
        // JobOrderResource::collection($job_order_request);

        // return GlobalFunction::responseFunction(
        //     Message::PURCHASE_REQUEST_DISPLAY,
        //     $job_order_request
        // );
    }
}
