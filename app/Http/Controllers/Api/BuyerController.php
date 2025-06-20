<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Buyer;
use App\Models\BuyerPO;
use App\Models\POItems;
use App\Models\PRItems;
use App\Models\RROrders;
use App\Models\PoHistory;
use App\Response\Message;
use App\Models\LogHistory;
use App\Models\POSettings;
use App\Models\PoApprovers;
use Illuminate\Http\Request;
use App\Models\POTransaction;
use App\Models\PRTransaction;
use App\Models\RRTransaction;
use App\Http\Requests\BDisplay;
use App\Models\BuyerJobOrderPO;
use App\Models\JOPOTransaction;
use App\Functions\GlobalFunction;
use App\Http\Requests\BPADisplay;
use App\Http\Resources\PoResource;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\JoPoResource;
use App\Http\Resources\PRPOResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\PoItemResource;
use App\Http\Requests\PO\ReturnRequest;
use App\Http\Resources\PRItemsResource;
use App\Http\Resources\PRTransactionResource;
use App\Http\Requests\Buyer\UpdatePriceRequest;

class BuyerController extends Controller
{
    public function index(BDisplay $request)
    {
        $status = $request->status;
        $user_id = Auth()->user()->id;

        $purchase_request = Buyer::with([
            "order" => function ($query) {
                $query->with("category");
            },
            "approver_history",
            "log_history",
            "po_transaction",
            "po_transaction.order" => function ($query) {
                $query->withTrashed();
            },
            "po_transaction.approver_history",
            "po_transaction.log_history",
        ])
            ->orderByDesc("updated_at")
            ->useFilters()
            ->dynamicPaginate();

        $is_empty = $purchase_request->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        PRTransactionResource::collection($purchase_request);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $purchase_request
        );
    }

    public function index_po(BDisplay $request)
    {
        $status = $request->status;
        $user_id = Auth()->user()->id;

        $purchase_order = BuyerPO::with([
            "order" => function ($query) {
                $query->withTrashed();
            },
            "order.rr_orders.rr_transaction",
            "approver_history",
            "log_history",
            "rr_transaction",
        ])
            ->whereHas("order", function ($query) use ($user_id) {
                $query->where("buyer_id", $user_id)->withTrashed();
            })
            // ->when($status === "cancelled", function ($query) {
            //     $query->onlyTrashed()->whereHas("order", function ($subQuery) {
            //         $subQuery->onlyTrashed();
            //     });
            // })
            ->orderByDesc("updated_at")
            ->useFilters()
            ->dynamicPaginate();

        $is_empty = $purchase_order->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        PoResource::collection($purchase_order);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $purchase_order
        );
    }

    public function index_rr(Request $request)
    {
        $isAsset = $request->input("isAsset", false);
        $requestor = $request->input("requestor");

        $rr_transactions = RRTransaction::with([
            "rr_orders",
            "pr_transaction.vladimir_user",
            "pr_transaction.regular_user",
            "log_history.users",
            "pr_transaction",
            "po_transaction",
        ])
            ->whereHas("rr_orders", function ($query) {
                $query->where("remaining", "=", 0);
            })
            ->whereHas("pr_transaction", function ($query) use (
                $requestor,
                $isAsset
            ) {
                if ($isAsset) {
                    $query
                        ->where("module_name", "=", "Asset")
                        ->where("vrid", "LIKE", "%{$requestor}%");
                } else {
                    $query
                        ->where("module_name", "!=", "Asset")
                        ->whereHas("regular_user", function (
                            $regularQuery
                        ) use ($requestor) {
                            $regularQuery->where(
                                "id_number",
                                "LIKE",
                                "%{$requestor}%"
                            );
                        });
                }
            })
            ->orderByDesc("updated_at")
            ->useFilters();

        $rr_transactions = $rr_transactions->dynamicPaginate();

        if (!$rr_transactions) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $rr_transactions
        );
    }

    public function view(Request $request, $id)
    {
        $status = $request->status;
        $user_id = Auth()->user()->id;

        $purchase_order = POTransaction::where("id", $id)
            ->with([
                "order" => function ($query) use ($user_id) {
                    $query->where("buyer_id", $user_id)->withTrashed();
                },
                "order.category",
                "approver_history",
            ])
            ->withTrashed()
            ->orderByDesc("updated_at")
            ->get()
            ->first();

        if (!$purchase_order) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $purchase_collect = new PoResource($purchase_order);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $purchase_collect
        );
    }

    public function show(Request $request, $id)
    {
        $status = $request->status;
        $user_id = Auth()->user()->id;

        $purchase_order = POTransaction::where("id", $id)
            ->with(["order", "order.category", "approver_history"])
            ->withTrashed()
            ->orderByDesc("updated_at")
            ->get()
            ->first();

        if (!$purchase_order) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $purchase_collect = new PoResource($purchase_order);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $purchase_collect
        );
    }

    public function viewto_po(Request $request, $id)
    {
        $status = $request->status;
        $user_id = Auth()->user()->id;

        $purchase_order = Buyer::with([
            "users",
            "order" => function ($query) {
                $query
                    ->where(function ($query) {
                        $query
                            ->whereNull("remaining_qty")
                            ->orWhere("remaining_qty", "!=", 0);
                    })
                    ->with("category");
            },
            "approver_history",
            "po_transaction",
            "po_transaction.order",
        ])
            ->where("id", $id)
            ->orderByDesc("updated_at")
            ->first();

        if (!$purchase_order) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $purchase_order->order->each(function ($order) {
            $order->quantity = $order->quantity - $order->partial_received;
        });

        new PRPOResource($purchase_order);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $purchase_order
        );
    }

    public function update_price(UpdatePriceRequest $request)
    {
        $status = $request->status;
        $id = $request->po_id;
        $user_id = Auth()->user()->id;

        $purchase_order = POTransaction::with([
            "order" => function ($query) use ($user_id) {
                $query->where("buyer_id", $user_id);
            },
            "approver_history",
            "log_history",
        ])
            ->where("id", $id)
            ->first();

        $po_approvers = $purchase_order->approver_history()->get();

        $order = $request->orders;

        $updatedItems = [];
        $totalPriceSum = 0;
        $description = $request["po_description"];
        $oldDescription = $purchase_order->po_description;
        $oldSupplier = $purchase_order->supplier_name;
        $newSupplier = $request["supplier_name"];
        $oldBuyer = $purchase_order->buyer_name;
        $newBuyer = $request["buyer_name"];
        $priceIncreased = false;
        $pricesChanged = false;
        $supplierChanged = $oldSupplier !== $newSupplier;

        foreach ($order as $values) {
            $order_id = $values["id"];
            $values["remarks"];
            $poItem = POItems::where("id", $order_id)->first();

            if ($poItem) {
                $oldPrice = $poItem->price;
                $newPrice = $values["price"];
                $newTotalPrice = $poItem->quantity * $newPrice;
                $poItemName = $poItem->item_name;

                if ($newPrice != $oldPrice) {
                    $pricesChanged = true;
                    if ($newPrice > $oldPrice) {
                        $priceIncreased = true;
                    }
                }

                $poItem->update([
                    "price" => $newPrice,
                    "total_price" => $newTotalPrice,
                    "remarks" => $values["remarks"],
                    "buyer_id" => $request["buyer_id"],
                    "buyer_name" => $request["buyer_name"],
                ]);

                $totalPriceSum += $newTotalPrice;

                $updatedItems[] = [
                    "id" => $order_id,
                    "item_name" => $poItemName,
                    "old_price" => $oldPrice,
                    "new_price" => $newPrice,
                    "new_supplier" => $newSupplier,
                    "old_supplier" => $oldSupplier,
                    "old_buyer" => $oldBuyer,
                    "new_buyer" => $newBuyer,
                ];
            }
        }

        $activityDescription =
            "Purchase Order ID: " .
            $id .
            " has been updated by UID: " .
            $user_id;

        if ($pricesChanged) {
            $activityDescription .= " prices for PO items: ";
            foreach ($updatedItems as $item) {
                $activityDescription .= "Item ID {$item["id"]}: {$item["item_name"]} {$item["old_price"]} -> {$item["new_price"]}, ";
            }
        }

        $activityDescription = rtrim($activityDescription, ", ");

        if ($supplierChanged) {
            $activityDescription .= ". Supplier changed from $oldSupplier to $newSupplier";
        }

        if ($oldBuyer !== $newBuyer) {
            $activityDescription .= ". Buyer changed from $oldBuyer to $newBuyer";
        }

        if ($description !== $oldDescription) {
            $activityDescription .=
                " with description changes from " .
                $oldDescription .
                " to " .
                $description;
        }

        $activityDescription .= ".";

        LogHistory::create([
            "activity" => $activityDescription,
            "po_id" => $id,
            "action_by" => $user_id,
        ]);

        $updateData = [
            "po_description" => $request["po_description"],
            "description" => $request["po_description"],
            "total_item_price" => $totalPriceSum,
            "updated_by" => $user_id,
            "supplier_id" => $request->supplier_id,
            "supplier_name" => $request->supplier_name,
            "pcf_remarks" => $request->pcf_remarks,
            "ship_to" => $request->ship_to,
            "buyer_id" => $request->buyer_id,
            "buyer_name" => $request->buyer_name,
            "edit_remarks" => $request->edit_remarks,
        ];

        if ($request->boolean("returned_po")) {
            $updateData["status"] = "Pending";
        }

        $purchase_order->update($updateData);

        if ($priceIncreased || $supplierChanged) {
            $purchase_order->update([
                "status" => "Pending",
                "layer" => "1",
                "rejected_at" => null,
            ]);

            $po_settings = POSettings::where(
                "company_id",
                $purchase_order->company_id
            )
                ->get()
                ->first();

            $highestPriceRange = PoApprovers::max("price_range");

            if ($totalPriceSum >= $highestPriceRange) {
                foreach ($po_approvers as $po_approver) {
                    $po_approver->update([
                        "approved_at" => null,
                        "rejected_at" => null,
                    ]);
                }

                $approvers = PoApprovers::where(
                    "price_range",
                    ">=",
                    $highestPriceRange
                )
                    ->where("po_settings_id", $po_settings->company_id)
                    ->get();
                $po_approver_history = $purchase_order
                    ->approver_history()
                    ->first();

                foreach ($approvers as $index) {
                    $existing_approver = PoHistory::where(
                        "po_id",
                        $po_approver_history->po_id
                    )
                        ->where("approver_id", $index["approver_id"])
                        ->first();

                    if (!$existing_approver) {
                        PoHistory::create([
                            "po_id" => $po_approver_history->po_id,
                            "approver_id" => $index["approver_id"],
                            "approver_name" => $index["approver_name"],
                            "layer" => $index["layer"],
                        ]);
                    }
                }
            }

            foreach ($po_approvers as $po_approver) {
                $po_approver->update([
                    "approved_at" => null,
                    "rejected_at" => null,
                ]);
            }
        }

        $poTransaction = $purchase_order->fresh(["order", "approver_history"]);

        new PoResource($poTransaction);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_UPDATE,
            $poTransaction
        );
    }

    public function return_pr_items(ReturnRequest $request)
    {
        $ids = $request->input("id", []);
        $user_id = Auth()->user()->id;

        $affected_rows = PRItems::whereIn("id", $ids)
            ->where("buyer_id", $user_id)
            ->update([
                "buyer_id" => null,
                "buyer_name" => null,
            ]);

        $updated_pr_items = PRItems::whereIn("id", $ids)->get();

        $prTransaction = PRItemsResource::collection($updated_pr_items);

        return GlobalFunction::responseFunction(
            Message::ITEM_UPDATE,
            $prTransaction
        );
    }

    public function buyer_badge(Request $request)
    {
        $user_id = Auth()->user()->id;
        $tagged_request = Buyer::with(
            "order",
            "approver_history",
            "log_history",
            "po_transaction",
            "po_transaction.order",
            "po_transaction.approver_history",
            "po_transaction.log_history"
        )
            ->with([
                "order" => function ($query) use ($user_id) {
                    $query
                        ->where("buyer_id", $user_id)
                        ->whereNull("supplier_id");
                },
            ])
            ->whereHas("order", function ($query) use ($user_id) {
                $query->where("buyer_id", $user_id)->whereNull("supplier_id");
            })
            ->where("status", "Approved")
            ->whereNotNull("approved_at")
            ->count();

        $for_po_approval = Buyer::withCount([
            "po_transaction" => function ($query) use ($user_id) {
                $query
                    ->whereNull("deleted_at")
                    ->where(function ($subQuery) {
                        $subQuery
                            ->where("status", "Pending")
                            ->orWhere("status", "For Approval");
                    })
                    ->whereHas("order", function ($orderQuery) use ($user_id) {
                        $orderQuery->where("buyer_id", $user_id);
                    });
            },
        ])
            ->get()
            ->sum("po_transaction_count");

        $po_approved = BuyerPO::whereHas("order", function ($orderQuery) use (
            $user_id
        ) {
            $orderQuery->where("buyer_id", $user_id);
        })
            ->where("status", "For Receiving")
            ->whereNotNull("approved_at")
            ->whereNull("cancelled_at")
            ->whereNull("rejected_at")
            ->count();

        $rejected = Buyer::whereHas("po_transaction", function ($query) use (
            $user_id
        ) {
            $query
                ->where("status", "Reject")
                ->whereNotNull("rejected_at")
                ->whereHas("order", function ($subQuery) use ($user_id) {
                    $subQuery->where("buyer_id", $user_id);
                })
                ->whereHas("approver_history", function ($subQuery) {
                    $subQuery->whereNotNull("rejected_at");
                });
        })
            ->whereNotNull("approved_at")
            ->withCount([
                "po_transaction" => function ($query) use ($user_id) {
                    $query
                        ->where("status", "Reject")
                        ->whereNotNull("rejected_at")
                        ->whereHas("order", function ($subQuery) use (
                            $user_id
                        ) {
                            $subQuery->where("buyer_id", $user_id);
                        })
                        ->whereHas("approver_history", function ($subQuery) {
                            $subQuery->whereNotNull("rejected_at");
                        });
                },
            ])
            ->get()
            ->sum("po_transaction_count");

        $cancelled = BuyerPO::with([
            "order" => function ($query) {
                $query->withTrashed();
            },
        ])
            ->withTrashed()
            ->where("status", "Cancelled")
            ->whereHas("order", function ($query) use ($user_id) {
                $query->where("buyer_id", $user_id)->withTrashed();
            })
            ->whereNotNull("cancelled_at")
            ->count();

        $pending_to_received = BuyerPO::where("status", "For Receiving")
            ->whereHas("order", function ($query) use ($user_id) {
                $query
                    ->where("buyer_id", $user_id)
                    ->where("quantity_serve", ">", 0)
                    ->whereColumn("quantity_serve", "<", "quantity");
            })
            ->count();

        $completed_rr = BuyerPO::whereHas("order", function ($query) use (
            $user_id
        ) {
            $query
                ->where("buyer_id", $user_id)
                ->whereColumn("quantity_serve", ">=", "quantity");
        })
            ->where("status", "For Receiving")
            ->whereHas("rr_transaction")
            ->whereNotNull("approved_at")
            ->whereNull("rejected_at")
            ->whereNull("cancelled_at")
            ->count();

        $jo_approval = BuyerJobOrderPO::whereHas("jo_po_orders", function (
            $query
        ) use ($user_id) {
            $query->where("buyer_id", $user_id);
        })
            ->whereIn("status", ["Pending", "For Approval"])
            ->whereNull("approved_at")
            ->whereNull("rejected_at")
            ->whereNull("cancelled_at")
            ->count();

        $jo_approved = BuyerJobOrderPO::whereHas("jo_po_orders", function (
            $query
        ) use ($user_id) {
            $query->where("buyer_id", $user_id);
        })
            ->where("status", "For Receiving")
            ->whereNotNull("approved_at")
            ->count();

        $jo_rejected = BuyerJobOrderPO::whereHas("jo_po_orders", function (
            $query
        ) use ($user_id) {
            $query->where("buyer_id", $user_id);
        })
            ->where("status", "Reject")
            ->whereNull("cancelled_at")
            ->whereNotNull("rejected_at")
            ->count();

        $jo_cancelled = BuyerJobOrderPO::whereHas("jo_po_orders", function (
            $query
        ) use ($user_id) {
            $query->where("buyer_id", $user_id);
        })
            ->where("status", "Cancelled")
            ->whereNotNull("rejected_at")
            ->whereNotNull("cancelled_at")
            ->count();

        $jo_pending_to_received = BuyerJobOrderPO::whereHas(
            "jo_po_orders",
            function ($subQuery) use ($user_id) {
                $subQuery
                    ->where("buyer_id", $user_id)
                    ->where("quantity_serve", ">", 0)
                    ->whereColumn("quantity_serve", "<", "quantity");
            }
        )
            ->where("status", "For Receiving")
            ->whereNotNull("approved_at")
            ->whereNull("rejected_at")
            ->whereNull("cancelled_at")
            ->count();

        $jo_completed = BuyerJobOrderPO::whereHas("jo_po_orders", function (
            $query
        ) use ($user_id) {
            $query
                ->where("buyer_id", $user_id)
                ->whereColumn("quantity_serve", ">=", "quantity");
        })
            ->where("status", "For Receiving")
            ->whereNotNull("approved_at")
            ->whereNull("rejected_at")
            ->whereNull("cancelled_at")
            ->count();

        $result = [
            "tagged_request" => $tagged_request,
            "for_po_approval" => $for_po_approval,
            "po_approved" => $po_approved,
            "rejected" => $rejected,
            "cancelled" => $cancelled,
            "pending_to_received" => $pending_to_received,
            "completed_rr" => $completed_rr,
            "jo_approval" => $jo_approval,
            "jo_approved" => $jo_approved,
            "jo_rejected" => $jo_rejected,
            "jo_cancelled" => $jo_cancelled,
            "jo_pending_to_reeived" => $jo_pending_to_received,
            "jo_completed" => $jo_completed,
        ];

        return GlobalFunction::responseFunction(
            Message::DISPLAY_COUNT,
            $result
        );
    }

    public function return_po(ReturnRequest $request)
    {
        $reason = $request->reason;
        $buyer_id = Auth()->user()->id;
        $po_id = $request->po_id;
        $po_transaction = POTransaction::where("id", $po_id)->first();

        if (!$po_transaction) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $po_transaction->update(["status" => "Return", "reason" => $reason]);

        $activityDescription =
            "Purchase Order ID: " .
            $po_id .
            " has been returned by UID: " .
            $buyer_id .
            " Reason: " .
            $reason;

        LogHistory::create([
            "activity" => $activityDescription,
            "po_id" => $po_id,
            "action_by" => $buyer_id,
        ]);

        // $pr_id = $po_transaction->pr_number;

        // $item_ids = $request->item_id;

        // $pr_items = PRTransaction::where("id", $pr_id)
        //     ->whereHas("order", function ($query) use ($buyer_id) {
        //         $query->where("buyer_id", $buyer_id);
        //     })
        //     ->with([
        //         "order" => function ($query) use ($buyer_id) {
        //             $query->where("buyer_id", $buyer_id);
        //         },
        //     ])
        //     ->first();

        // $po_transaction
        //     ->order()
        //     ->where("buyer_id", $buyer_id)
        //     ->update([
        //         "buyer_id" => null,
        //         "buyer_name" => null,
        //         "supplier_id" => null,
        //     ]);

        // $pr_items
        //     ->order()
        //     ->whereIn("item_id", $item_ids)
        //     ->update([
        //         "buyer_id" => null,
        //         "buyer_name" => null,
        //         "supplier_id" => null,
        //     ]);

        $po_approvers = $po_transaction->approver_history()->update([
            "approved_at" => null,
        ]);

        $fresh = $po_transaction->fresh("order");
        new PoResource($fresh);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_RETURN,
            $fresh
        );
    }

    public function item_unit_price(Request $request, $id)
    {
        $user_id = $request->user_id;
        $item_code = $id;
        $item_name = $request->item_name;
        $previous_unit_price = POItems::when($item_code == 0, function (
            $query
        ) use ($item_code, $item_name) {
            $query->when($item_name, function ($query) use ($item_name) {
                // $query->where("item_name", "like", "%" . $item_name . "%");
                $query->whereRaw("LOWER(item_name) = ?", [
                    strtolower($item_name),
                ]);
            });
        })
            ->when($item_code, function ($query) use ($item_code) {
                $query->where("item_code", $item_code);
            })
            ->whereHas("po_transaction", function ($query) use ($user_id) {
                $query->where("user_id", $user_id);
            })
            ->get();

        $item_collect = PoItemResource::collection($previous_unit_price);

        return GlobalFunction::responseFunction(
            Message::ITEM_PREVIOUS_PRICE,
            $item_collect
        );
    }

    public function place_order($id)
    {
        $purchase_order = POTransaction::where("id", $id)
            ->get()
            ->first();

        if (!$purchase_order) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $place_order = $request->place_order;

        $purchase_order->update(["place_order" => $place_order]);

        $buyer_id = Auth()->user()->id;

        $activityDescription =
            "Purchase Order ID: " .
            $purchase_order->id .
            " has been place ordered by UID: " .
            $buyer_id;

        LogHistory::create([
            "activity" => $activityDescription,
            "po_id" => $purchase_order->id,
            "action_by" => $buyer_id,
        ]);

        $place_order = new PoResource($purchase_order);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORER_PLACE_ORDER,
            $place_order
        );
    }

    public function index_jo(BDisplay $request)
    {
        $user_id = Auth()->user()->id;
        $status = $request->status;
        $job_order_request = BuyerJobOrderPO::with(
            "jo_po_orders",
            "jo_po_orders.rr_orders.jo_rr_transaction"
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
}
