<?php

namespace App\Http\Controllers\Api;

use App\Models\Buyer;
use App\Models\POItems;
use App\Models\PRItems;
use App\Models\PoHistory;
use App\Response\Message;
use App\Models\LogHistory;
use App\Models\POSettings;
use App\Models\PoApprovers;
use Illuminate\Http\Request;
use App\Models\POTransaction;
use App\Models\PRTransaction;
use App\Http\Requests\BDisplay;
use App\Functions\GlobalFunction;
use App\Http\Requests\BPADisplay;
use App\Http\Resources\PoResource;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\PRPOResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\PoItemResource;
use App\Http\Resources\PRItemsResource;
use App\Http\Requests\Buyer\ReturnRequest;
use App\Http\Resources\PRTransactionResource;
use App\Http\Requests\Buyer\UpdatePriceRequest;

class BuyerController extends Controller
{
    public function index(BDisplay $request)
    {
        $status = $request->status;
        $user_id = Auth()->user()->id;

        $purchase_request = Buyer::with(
            "order",
            "approver_history",
            "log_history",
            "po_transaction",
            "po_transaction.order",
            "po_transaction.approver_history",
            "po_transaction.log_history"
        )
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

    public function view(Request $request, $id)
    {
        $status = $request->status;
        $user_id = Auth()->user()->id;

        $purchase_order = POTransaction::where("id", $id)
            ->with([
                "order" => function ($query) use ($user_id) {
                    $query->where("buyer_id", $user_id);
                },
                "approver_history",
            ])
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
            "order",
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
        $oldSupplier = $purchase_order->supplier_name;
        $newSupplier = $request["supplier_name"];
        $priceIncreased = false;

        foreach ($order as $values) {
            $order_id = $values["id"];
            $poItem = POItems::where("id", $order_id)->first();

            if ($poItem) {
                $oldPrice = $poItem->price;
                $newPrice = $values["price"];
                $newTotalPrice = $poItem->quantity * $newPrice;

                if ($newPrice > $oldPrice) {
                    $priceIncreased = true;
                }

                $poItem->update([
                    "price" => $newPrice,
                    "total_price" => $newTotalPrice,
                    "remarks" => $request["remarks"],
                ]);

                $totalPriceSum += $newTotalPrice;

                $updatedItems[] = [
                    "id" => $order_id,
                    "old_price" => $oldPrice,
                    "new_price" => $newPrice,
                    "new_supplier" => $newSupplier,
                    "old_supplier" => $oldSupplier,
                ];
            }
        }

        $activityDescription =
            "Purchase Order ID: " .
            $id .
            " has been updated prices for PO items: ";
        foreach ($updatedItems as $item) {
            $activityDescription .= "Item ID {$item["id"]}: {$item["old_price"]} -> {$item["new_price"]}, ";
        }
        $activityDescription = rtrim($activityDescription, ", ");

        if ($oldSupplier !== $newSupplier) {
            $activityDescription .= ". Supplier changed from $oldSupplier to $newSupplier";
        }

        $activityDescription .= ".";

        LogHistory::create([
            "activity" => $activityDescription,
            "po_id" => $id,
            "action_by" => $user_id,
        ]);

        $purchase_order->update([
            "po_description" => $request["po_description"],
            "total_item_price" => $totalPriceSum,
            "updated_by" => $user_id,
            "supplier_id" => $request->supplier_id,
            "supplier_name" => $request->supplier_name,
            "edit_remarks" => $request->edit_remarks,
        ]);

        if ($priceIncreased) {
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

        $result = [
            "tagged_request" => $tagged_request,
        ];

        return GlobalFunction::responseFunction(
            Message::DISPLAY_COUNT,
            $result
        );
    }

    public function return_po(Request $request)
    {
        $reason = $request->reason;
        $buyer_id = Auth()->user()->id;
        $po_id = $request->po_id;
        $po_transaction = POTransaction::where("po_number", $po_id)
            ->whereHas("order", function ($query) use ($buyer_id) {
                $query->where("buyer_id", $buyer_id);
            })
            ->with([
                "order" => function ($query) use ($buyer_id) {
                    $query->where("buyer_id", $buyer_id);
                },
            ])
            ->first();

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

        $pr_id = $po_transaction->pr_number;

        $item_ids = $request->item_id;

        $pr_items = PRTransaction::where("id", $pr_id)
            ->whereHas("order", function ($query) use ($buyer_id) {
                $query->where("buyer_id", $buyer_id);
            })
            ->with([
                "order" => function ($query) use ($buyer_id) {
                    $query->where("buyer_id", $buyer_id);
                },
            ])
            ->first();

        $po_transaction
            ->order()
            ->where("buyer_id", $buyer_id)
            ->update([
                "buyer_id" => null,
                "buyer_name" => null,
                "supplier_id" => null,
            ]);

        $pr_items
            ->order()
            ->whereIn("item_id", $item_ids)
            ->update([
                "buyer_id" => null,
                "buyer_name" => null,
                "supplier_id" => null,
            ]);

        $fresh = $po_transaction->fresh("order");
        new PoResource($fresh);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_RETURN,
            $fresh
        );
    }

    public function item_unit_price($id)
    {
        $item_code = $id;
        $previous_unit_price = POItems::where("item_code", $item_code)->get();

        $item_collect = PoItemResource::collection($previous_unit_price);

        return GlobalFunction::responseFunction(
            Message::ITEM_PREVIOUS_PRICE,
            $item_collect
        );
    }
}
