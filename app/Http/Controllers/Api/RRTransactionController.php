<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
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
use App\Models\RRTransactionV2;
use App\Functions\GlobalFunction;
use App\Helpers\RRHelperFunctions;
use App\Http\Resources\PoResource;
use App\Http\Resources\RRResource;
use App\Http\Requests\PO\PORequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\PRViewRequest;
use App\Http\Resources\RRV2Resource;
use App\Helpers\BadgeHelperFunctions;
use App\Http\Resources\RRSyncDisplay;
use App\Http\Resources\RROrdersResource;
use Illuminate\Pagination\AbstractPaginator;
use App\Http\Resources\PRTransactionResource;
use App\Http\Requests\AssetVladimir\UpdateRequest;
use App\Http\Requests\ReceivedReceipt\StoreRequest;
use App\Http\Requests\ReceivedReceipt\CancelRequest;
use App\Http\Resources\LogHistory\LogHistoryResource;

class RRTransactionController extends Controller
{
    public function index(Request $request)
    {
        $rr_transaction = RRTransaction::with([
            "rr_orders" => function ($query) {
                $query->withTrashed();
            },
            "log_history",
            "log_history.users",
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
        $rr_transaction = RRTransaction::with([
            "rr_orders.po_transaction" => function ($query) {
                $query->withTrashed();
            },
            "rr_orders.pr_transaction",
            "rr_orders.order.uom",
            "log_history",
        ])
            ->where("id", $id)
            ->orderByDesc("updated_at")
            ->first();

        if (!$rr_transaction) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $collect_rr_transaction = new RRV2Resource($rr_transaction);

        return GlobalFunction::responseFunction(
            Message::RR_DISPLAY,
            $collect_rr_transaction
        );
    }

    public function store(StoreRequest $request)
    {
        $user_id = Auth()->user()->id;
        $po_transaction = POTransaction::findOrFail($request->po_no);

        $assetType = $po_transaction->type_name;

        if ($assetType == "Asset") {
            return GlobalFunction::invalid([
                "message" =>
                    "Undermaintenance. Integration is currently disabled.",
            ]);
        }

        $pr_id_exists = RRHelperFunctions::checkPRExists(
            $po_transaction,
            $request->pr_no
        );
        if (!$pr_id_exists) {
            return GlobalFunction::invalid(Message::NOT_FOUND);
        }

        $orders = $request->order;
        $po_items = RRHelperFunctions::getPoItems($orders);

        $validation_result = RRHelperFunctions::validateQuantities(
            $orders,
            $po_items
        );
        if ($validation_result !== true) {
            return $validation_result;
        }

        $rr_transaction = RRHelperFunctions::createRRTransaction(
            $po_transaction,
            $user_id,
            $request->tagging_id
        );

        $itemDetails = RRHelperFunctions::processOrders(
            $orders,
            $po_items,
            $rr_transaction
        );

        RRHelperFunctions::createLogHistory(
            $rr_transaction,
            $user_id,
            $itemDetails
        );

        // $allItemsReceived = $po_transaction->po_items->every(function ($item) {
        //     return $item->quantity === $item->quantity_serve;
        // });

        // if ($allItemsReceived) {
        //     $po_transaction->status = "Received";
        //     $po_transaction->save();
        // }

        $rr_collect = new RRResource($rr_transaction);

        return GlobalFunction::responseFunction(Message::RR_SAVE, $rr_collect);
    }

    public function update(Request $request, $id)
    {
        $rr_number = $id;
        $orders = $request->order;
        $rr_transaction = RRHelperFunctions::checkRRExists($rr_number);

        if (!$rr_transaction) {
            return GlobalFunction::invalid(Message::NOT_FOUND);
        }
        $itemIds = [];
        $rr_collect = [];
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        RRHelperFunctions::validateQuantityReceiving($orders, $request);

        foreach ($orders as $index => $values) {
            $rr_orders_id = $request["order"][$index]["item_id"];

            $po_orders = POItems::where("id", $rr_orders_id)
                ->get()
                ->first();

            $add_previous = RRHelperFunctions::createRROrderAndUpdatePOItem(
                $rr_orders_id,
                $request,
                $po_orders,
                $index,
                $rr_number,
                $itemDetails
            );

            $rr_collect[] = new RROrdersResource($add_previous);
        }

        $user_id = auth()->id();

        RRHelperFunctions::logRRTransaction(
            $rr_transaction,
            $itemDetails,
            $user_id
        );

        return GlobalFunction::responseFunction(
            Message::RR_UPDATE,
            $rr_collect
        );
    }

    public function index_po_approved(PRViewRequest $request)
    {
        $status = $request->status;
        $po_approve = POTransaction::with([
            "order",
            "pr_transaction" => fn($q) => $q->withTrashed(),
            "approver_history",
            "rr_transaction.rr_orders",
        ])
            ->orderByDesc("created_at")
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
        $po_approve = POTransaction::withTrashed()
            ->with([
                "rr_transaction",
                "order" => function ($query) use ($id) {
                    $po = POTransaction::withTrashed()->find($id);
                    if ($po && $po->trashed()) {
                        $query->withTrashed();
                    }
                },
            ])
            ->find($id);

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
        // $purchase_request = POTransaction::with([
        //     "rr_transaction.rr_orders",
        //     "order",
        // ])
        //     ->where("module_name", "Asset")
        //     ->whereHas("rr_transaction", function ($query) use ($id) {
        //         $query->where("id", $id);
        //     })
        //     ->orderByDesc("updated_at")
        //     ->useFilters()
        //     ->dynamicPaginate();

        $rr_transaction = RRTransactionV2::where("id", $id)
            ->whereHas("po_transaction", function ($query) {
                $query->where("module_name", "Asset");
            })
            ->with(["rr_orders.order", "po_transaction"])
            ->first();

        if (!$rr_transaction) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $rr_response = new RRSyncDisplay($rr_transaction);
        return GlobalFunction::responseFunction(
            Message::RR_DISPLAY,
            $rr_response
        );
    }

    public function asset_syncs(UpdateRequest $request)
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
        $pr_items = PRItems::with(
            "po_order.rr_orders.po_transaction",
            "po_order.rr_orders.rr_transaction",
            "uom",
            "transaction.approver_history",
            "transaction.users"
        )
            ->useFilters()
            ->dynamicPaginate();
        // $purchase_order = PRTransaction::with(
        //     "users",
        //     "order",
        //     "approver_history"
        // )
        //     ->useFilters()
        //     ->dynamicPaginate();

        $is_empty = $pr_items->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $pr_items
        );
    }

    public function report_po(PRViewRequest $request)
    {
        $buyer = $request->input("buyer");
        $from = $request->input("from");
        $to = $request->input("to");
        $po_items = POItems::with(
            "uom",
            "pr_item",
            "po_transaction.users",
            "po_transaction.pr_transaction.approver_history",
            "po_transaction.approver_history",
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

    public function report_rr(Request $request)
    {
        $user_id = Auth()->user()->id;
        $type = $request->type;
        $from_po_date = $request->from_po_date;
        $to_po_date = $request->to_po_date;
        $fa_summary = $request->boolean("fa_summary");
        $per_user = $request->boolean("per_user");

        $query = RROrders::with([
            "order.supplier",
            "order.uom",
            "rr_transaction" => function ($query) {
                $query->withTrashed();
            },
            "po_transaction" => function ($query) {
                $query->withTrashed();
            },
            "po_transaction.pr_transaction" => function ($query) {
                $query->withTrashed();
            },
            "po_transaction.pr_transaction.vladimir_user",
            "po_transaction.pr_transaction.regular_user",
            // "po_transaction.pr_transaction.users",
            "po_transaction.pr_transaction.approver_history",
            "po_transaction.company",
            "po_transaction.department",
            "po_transaction.department_unit",
            "po_transaction.sub_unit",
            "po_transaction.location",
            "po_transaction.account_title",
            "po_transaction.account_title.account_type",
            "po_transaction.account_title.account_group",
            "po_transaction.account_title.account_sub_group",
            "po_transaction.account_title.financial_statement",
            "po_transaction.approver_history",
        ])
            ->when($fa_summary == true, function ($query) {
                $query->whereHas("po_transaction", function ($subQuery) {
                    $subQuery->where("module_name", "Asset");
                });
            })
            ->when($per_user == true, function ($query) use ($user_id) {
                $query->whereHas("rr_transaction", function ($subQuery) use (
                    $user_id
                ) {
                    $subQuery->where("received_by", $user_id);
                });
            })
            ->whereNull("deleted_at");
        if ($type === "for_user") {
            $query->whereHas("po_transaction", function ($q) use ($user_id) {
                $q->where("user_id", $user_id);
            });
        }

        if ($from_po_date && $to_po_date) {
            $query->whereHas("po_transaction", function ($q) use (
                $from_po_date,
                $to_po_date
            ) {
                $q->whereBetween("created_at", [$from_po_date, $to_po_date]);
            });
        } elseif ($from_po_date) {
            $query->whereHas("po_transaction", function ($q) use (
                $from_po_date
            ) {
                $q->whereDate("created_at", ">=", $from_po_date);
            });
        } elseif ($to_po_date) {
            $query->whereHas("po_transaction", function ($q) use ($to_po_date) {
                $q->whereDate("created_at", "<=", $to_po_date);
            });
        }
        $rr_orders = $query->useFilters()->dynamicPaginate();
        $is_empty = $rr_orders->isEmpty();
        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        if ($rr_orders instanceof AbstractPaginator) {
            $rr_orders->getCollection()->transform(function ($rr_order) {
                if (
                    isset($rr_order->po_transaction) &&
                    $rr_order->po_transaction->deleted_at === null
                ) {
                    $quantity = $rr_order->order->quantity ?? 0;
                    $quantity_served = $rr_order->order->quantity_serve ?? 0;
                    $rr_order->po_status = $rr_order->po_transaction->status;
                    if ($quantity_served >= $quantity) {
                        $rr_order->po_transaction->status = "Received";
                    }

                    // Transform user based on module type
                    if (isset($rr_order->po_transaction->pr_transaction)) {
                        $pr_transaction =
                            $rr_order->po_transaction->pr_transaction;
                        $pr_transaction->users =
                            $rr_order->po_transaction->module_name ===
                                "Asset" && $pr_transaction->vladimir_user
                                ? [
                                    "id" => $pr_transaction->vladimir_user->id,
                                    "employee_id" =>
                                        $pr_transaction->vladimir_user
                                            ->employee_id,
                                    "username" =>
                                        $pr_transaction->vladimir_user
                                            ->username,
                                    "first_name" =>
                                        $pr_transaction->vladimir_user
                                            ->firstname,
                                    "last_name" =>
                                        $pr_transaction->vladimir_user
                                            ->lastname,
                                ]
                                : ($pr_transaction->regular_user
                                    ? [
                                        "prefix_id" =>
                                            $pr_transaction->regular_user
                                                ->prefix_id,
                                        "id_number" =>
                                            $pr_transaction->regular_user
                                                ->id_number,
                                        "first_name" =>
                                            $pr_transaction->regular_user
                                                ->first_name,
                                        "middle_name" =>
                                            $pr_transaction->regular_user
                                                ->middle_name,
                                        "last_name" =>
                                            $pr_transaction->regular_user
                                                ->last_name,
                                        "mobile_no" =>
                                            $pr_transaction->regular_user
                                                ->mobile_no,
                                    ]
                                    : []);

                        // Unset the original user relationships to avoid duplication
                        unset($pr_transaction->vladimir_user);
                        unset($pr_transaction->regular_user);
                    }
                }
                return $rr_order;
            });
        } else {
            $rr_orders = $rr_orders->transform(function ($rr_order) {
                if (
                    isset($rr_order->po_transaction) &&
                    $rr_order->po_transaction->deleted_at === null
                ) {
                    $quantity = $rr_order->order->quantity ?? 0;
                    $quantity_served = $rr_order->order->quantity_serve ?? 0;
                    $rr_order->po_status = $rr_order->po_transaction->status;
                    if ($quantity_served >= $quantity) {
                        $rr_order->po_transaction->status = "Received";
                    }

                    // Transform user based on module type
                    if (isset($rr_order->po_transaction->pr_transaction)) {
                        $pr_transaction =
                            $rr_order->po_transaction->pr_transaction;
                        $pr_transaction->users =
                            $rr_order->po_transaction->module_name ===
                                "Asset" && $pr_transaction->vladimir_user
                                ? [
                                    "id" => $pr_transaction->vladimir_user->id,
                                    "employee_id" =>
                                        $pr_transaction->vladimir_user
                                            ->employee_id,
                                    "username" =>
                                        $pr_transaction->vladimir_user
                                            ->username,
                                    "first_name" =>
                                        $pr_transaction->vladimir_user
                                            ->firstname,
                                    "last_name" =>
                                        $pr_transaction->vladimir_user
                                            ->lastname,
                                ]
                                : ($pr_transaction->regular_user
                                    ? [
                                        "prefix_id" =>
                                            $pr_transaction->regular_user
                                                ->prefix_id,
                                        "id_number" =>
                                            $pr_transaction->regular_user
                                                ->id_number,
                                        "first_name" =>
                                            $pr_transaction->regular_user
                                                ->first_name,
                                        "middle_name" =>
                                            $pr_transaction->regular_user
                                                ->middle_name,
                                        "last_name" =>
                                            $pr_transaction->regular_user
                                                ->last_name,
                                        "mobile_no" =>
                                            $pr_transaction->regular_user
                                                ->mobile_no,
                                    ]
                                    : []);

                        // Unset the original user relationships to avoid duplication
                        unset($pr_transaction->vladimir_user);
                        unset($pr_transaction->regular_user);
                    }
                }
                return $rr_order;
            });
        }
        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $rr_orders
        );
    }
    public function cancel_rr(CancelRequest $request, $id)
    {
        $reason = $request->reason;
        $vlad_user = $request->v_name;
        $rdf_id = $request->rdf_id;

        $user = $vlad_user
            ? $rdf_id . " (" . $vlad_user . ")"
            : ($user = Auth()->user()->id);

        $type = $vlad_user ? "returned" : "cancelled";

        $rr_transaction = RRTransaction::where("id", $id)
            ->with("rr_orders", "po_order")
            ->first();

        if (!$rr_transaction) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        // ✅ 7-day cancellation limit check
        // $createdAt = Carbon::parse($rr_transaction->created_at);
        // $now = Carbon::now();

        // if ($createdAt->diffInDays($now) > 7) {
        //     return response()->json([
        //         "message" => "Transaction can no longer be $type. It has exceeded the 7-day cancellation window.",
        //     ], 403);
        // }

        $po_orders = $rr_transaction->rr_orders->pluck("item_id")->toArray();

        $po_items = POItems::whereIn("id", $po_orders)->get();

        foreach ($rr_transaction->rr_orders as $rr_order) {
            $po_item = $po_items->where("id", $rr_order->item_id)->first();

            if ($po_item) {
                $po_item->quantity_serve -= $rr_order->quantity_receive;
                $po_item->save();

                $pr_item = PRItems::find($po_item->pr_item_id);

                if ($pr_item) {
                    $pr_item->update([
                        "partial_received" =>
                            $pr_item->partial_received -
                            $rr_order->quantity_receive,
                        "remaining_qty" =>
                            $pr_item->quantity -
                            ($pr_item->partial_received -
                                $rr_order->quantity_receive),
                    ]);
                }
            }

            $rr_order->delete();
        }

        $activityDescription = "Received Receipt ID: {$rr_transaction->id} has been {$type} by UID: {$user}. Reason: {$reason}.";

        LogHistory::create([
            "activity" => $activityDescription,
            "rr_id" => $rr_transaction->id,
        ]);

        $cancelled_rr_transaction = $rr_transaction;

        $rr_transaction->update(["reason" => $reason]);
        $rr_transaction->delete();

        return GlobalFunction::responseFunction(
            Message::RR_CANCELLATION,
            $cancelled_rr_transaction
        );
    }

    public function rr_badge()
    {
        $user_id = Auth()->user()->id;

        $result = [
            "for_receiving" => BadgeHelperFunctions::forReceiving(),
            "for_receiving_user" => BadgeHelperFunctions::forReceivingUser(
                $user_id
            ),
            "for_receiving_job_order" => BadgeHelperFunctions::rrJobOrderCount(),
            "for_receiving_job_order_user" => BadgeHelperFunctions::rrJobOrderCountUser(
                $user_id
            ),
        ];

        return GlobalFunction::responseFunction(
            Message::DISPLAY_COUNT,
            $result
        );
    }
}
