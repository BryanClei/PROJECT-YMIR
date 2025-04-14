<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Type;
use App\Models\POItems;
use App\Models\PRItems;
use App\Models\JobItems;
use App\Models\PrHistory;
use App\Response\Message;
use App\Models\JoPoOrders;
use App\Models\LogHistory;
use App\Models\POSettings;
use App\Models\JoPoHistory;
use App\Models\PoApprovers;
use Illuminate\Http\Request;
use App\Models\POTransaction;
use App\Models\PRTransaction;
use App\Models\JOPOTransaction;
use App\Http\Requests\JODisplay;
use App\Http\Requests\PADisplay;
use App\Functions\GlobalFunction;
use App\Models\PurchaseAssistant;
use App\Http\Resources\PoResource;
use App\Http\Requests\PO\PORequest;
use App\Http\Resources\PAResources;
use App\Models\JobOrderTransaction;
use App\Models\PurchaseAssistantPO;
use App\Http\Controllers\Controller;
use App\Http\Requests\PRViewRequest;
use App\Http\Resources\JoPoResource;
use App\Http\Resources\PAPOResource;
use App\Http\Resources\PRPOResource;
use App\Models\JobOrderTransactionPA;
use App\Http\Resources\JobOrderResource;
use App\Http\Requests\JoPo\UpdateRequest;
use App\Http\Resources\PRTransactionResource;
use App\Http\Requests\PurchasingAssistant\StoreRequest;
use App\Http\Requests\JobOrderTransaction\CancelRequest;

class PAController extends Controller
{
    public function index(PADisplay $request)
    {
        $purchase_request = PurchaseAssistant::with([
            // "vladimir_user" => function ($query) {
            //     $query->when(request()->module_name === "Asset", function ($q) {
            //         return $q;
            //     });
            // },
            "regular_user" => function ($query) {
                $query->when(request()->module_name !== "Asset", function ($q) {
                    return $q;
                });
            },
            "approver_history",
            "log_history" => function ($query) {
                $query->orderBy("created_at", "desc");
            },
            "po_transaction",
            "po_transaction.order",
            "po_transaction.approver_history",
            "po_transaction.pr_approver_history",
            "po_transaction.log_history",
        ])
            ->useFilters()
            ->dynamicPaginate();

        if ($purchase_request->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        PRPOResource::collection($purchase_request);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $purchase_request
        );
    }

    public function index_purchase_order(PADisplay $request)
    {
        $purchase_request = PurchaseAssistantPO::with([
            "pr_transaction",
            "po_items",
            "approver_history",
            "log_history" => function ($query) {
                $query->orderBy("created_at", "desc");
            },
            "pr_approver_history",
        ])
            ->withTrashed()
            ->useFilters()
            ->dynamicPaginate();

        $is_empty = $purchase_request->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        PAPOResource::collection($purchase_request);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $purchase_request
        );
    }

    public function update(Request $request, $id)
    {
        $po_transaction = POTransaction::where("id", $id)
            ->get()
            ->first();

        if (!$po_transaction) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $po_transaction->update([
            "po_description" => $request->description,
            "description" => $request->description,
        ]);

        $pr_collect = new PoResource($po_transaction);

        return GlobalFunction::save(
            Message::PURCHASE_ORDER_UPDATE,
            $pr_collect
        );
    }

    public function index_jo(JODisplay $request)
    {
        $user_id = Auth()->user()->id;
        $status = $request->status;
        $job_order_request = JobOrderTransactionPA::with(
            "order",
            "approver_history",
            "log_history",
            "jo_po_transaction",
            "jo_po_transaction.jo_approver_history"
        )
            ->orderByDesc("updated_at")
            ->useFilters()
            ->dynamicPaginate();

        $is_empty = $job_order_request->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        JobOrderResource::collection($job_order_request);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $job_order_request
        );
    }

    public function view_jo(JODisplay $request, $id)
    {
        $status = $request->status;
        $user_id = Auth()->user()->id;
        $job_order_request = JobOrderTransactionPA::with([
            "order" => function ($query) use ($status) {
                $query->when($status === "for_po", function ($query) {
                    $query->whereNull("po_at");
                });
            },
            "approver_history",
            "log_history",
            "jo_po_transaction",
            "jo_po_transaction.jo_approver_history",
            "jo_transaction",
        ])
            ->where("id", $id)
            ->orderByDesc("updated_at")
            ->get()
            ->first();

        if (!$job_order_request) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        $job_collect = new JobOrderResource($job_order_request);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $job_collect
        );
    }

    public function index_jo_po(PRViewRequest $request)
    {
        $user_id = Auth()->user()->id;
        $status = $request->status;
        $job_order_request = JOPOTransaction::with(
            "jo_po_orders",
            "jo_approver_history",
            "log_history",
            "jo_transaction",
            "jo_transaction.users",
            "jo_transaction.log_history",
            "jo_transaction.approver_history"
        )
            ->whereNull("direct_po")
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

    public function view(PADisplay $request, $id)
    {
        $status = $request->status;
        $user_id = Auth()->user()->id;
        $purchase_request = PurchaseAssistant::where("id", $id)
            ->with([
                "order" => function ($query) use ($status, $user_id) {
                    $query
                        ->when($status === "to_po", function ($query) {
                            $query
                                ->whereNull("supplier_id")
                                ->whereNull("buyer_id");
                        })
                        ->when($status === "pending", function ($query) {
                            $query->whereNotNull("supplier_id");
                        })
                        ->when($status === "tagged_buyer", function ($query) {
                            $query
                                ->whereNotNull("buyer_id")
                                ->whereNull("supplier_id");
                        })
                        ->with("category")
                        ->where(function ($query) {
                            $query
                                ->whereNull("remaining_qty")
                                ->orWhere("remaining_qty", "!=", 0);
                        });
                },
                "order.warehouse",
                "order.item.small_tools",
                "users",
            ])
            ->orderByDesc("updated_at")
            ->get()
            ->first();

        if (!$purchase_request) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $purchase_request->order = $purchase_request->order
            ->filter(function ($order) {
                if ($order->remaining_qty === null) {
                    $order->remaining_qty = $order->quantity;
                }

                $partial_received = $order->partial_received ?? 0;

                $effective_quantity = $order->quantity - $partial_received;

                return $effective_quantity > 0;
            })
            ->values();

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $purchase_request
        );
    }

    public function viewpo(PADisplay $request, $id)
    {
        $status = $request->status;
        $user_id = Auth()->user()->id;

        $purchase_request = POTransaction::where("id", $id)
            ->with([
                "order" => function ($query) {
                    $query->withTrashed();
                },
                "order.items.small_tools",
                "order.warehouse",
            ])
            ->orderByDesc("updated_at")
            ->withTrashed()
            ->get()
            ->first();

        if (!$purchase_request) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        // new PoResource($purchase_request);

        // return GlobalFunction::responseFunction(
        //     Message::PURCHASE_ORDER_DISPLAY,
        //     $purchase_request
        // );

        $purchase_collet = new PoResource($purchase_request);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $purchase_collet
        );
    }

    public function update_jo(Request $request, $id)
    {
        $job_order = JOPOTransaction::where("id", $id)
            ->get()
            ->first();

        $not_found = JOPOTransaction::where("id", $id)->exists();

        $po_approvers = $job_order->jo_approver_history()->get();

        if (!$not_found) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $orders = $request->order;

        $newTotalPrice = array_sum(array_column($orders, "total_price"));

        $user_id = $request->user_id;

        $existingItems = JoPoOrders::where("jo_po_id", $id)->get();
        $updatedItems = [];

        foreach ($orders as $index => $values) {
            $existingItem = $existingItems->firstWhere("id", $values["id"]);

            if ($existingItem) {
                $item_name = $existingItem->description;
                $oldPrice = $existingItem->price;
                $newPrice = $values["price"];
                $oldTotalPrice = $existingItem->total_price;
                $newTotalPrice = $values["quantity"] * $values["price"];

                if (
                    $oldPrice != $newPrice ||
                    $oldTotalPrice != $newTotalPrice
                ) {
                    $updatedItems[] = [
                        "id" => $values["id"],
                        "name" => $item_name,
                        "old_price" => $oldPrice,
                        "new_price" => $newPrice,
                        "old_total_price" => $oldTotalPrice,
                        "new_total_price" => $newTotalPrice,
                    ];
                }

                $oldBuyerName = $originalItem["buyer_name"];
                $oldBuyerId = $originalItem["buyer_id"];
                $newBuyerName = $values["buyer_name"];
                $newBuyerId = $values["buyer_id"];

                if (
                    $oldBuyerName !== $newBuyerName ||
                    $oldBuyerId !== $newBuyerId
                ) {
                    if ($oldBuyerName === $newBuyerName) {
                        $buyerTaggingDetails[] = "Item ID {$values["id"]}: Buyer tagged as {$newBuyerName} (ID: {$newBuyerId})";
                    } else {
                        $buyerChangeDetails[] = "Item ID {$values["id"]}: Buyer changed from {$oldBuyerName} (ID: {$oldBuyerId}) to {$newBuyerName} (ID: {$newBuyerId})";
                    }
                }
            }
        }

        $job_order->update([
            "po_description" => $request->po_description,
            "date_needed" => $request->date_needed,
            "user_id" => $request->user_id,
            "type_id" => $request->type_id,
            "type_name" => $request->type_name,
            "business_unit_id" => $request->business_unit_id,
            "business_unit_name" => $request->business_unit_name,
            "company_id" => $request->company_id,
            "company_name" => $request->company_name,
            "department_id" => $request->department_id,
            "department_name" => $request->department_name,
            "department_unit_id" => $request->department_unit_id,
            "department_unit_name" => $request->department_unit_name,
            "location_id" => $request->location_id,
            "location_name" => $request->location_name,
            "sub_unit_id" => $request->sub_unit_id,
            "sub_unit_name" => $request->sub_unit_name,
            "account_title_id" => $request->account_title_id,
            "account_title_name" => $request->account_title_name,
            "module_name" => $request->module_name,
            "total_item_price" => $request->total_item_price,
            "supplier_id" => $request->supplier_id,
            "supplier_name" => $request->supplier_name,
            "status" => "Pending",
            "asset" => $request->asset,
            "sgp" => $request->sgp,
            "f1" => $request->f1,
            "f2" => $request->f2,
            "layer" => "1",
            "rush" => $request->rush,
            "outside_labor" => $request->outside_labor,
            "cap_ex" => $request->cap_ex,
            "direct_po" => $request->direct_po,
            "helpdesk_id" => $request->helpdesk_id,
            "cip_number" => $request->cip_number,
            "description" => $request->description,
        ]);

        $newOrders = collect($orders)
            ->pluck("id")
            ->toArray();
        $currentOrders = JoPoOrders::where("jo_po_id", $id)
            ->get()
            ->pluck("id")
            ->toArray();

        $currentOrdersPrice = JoPoOrders::where("jo_po_id", $id)
            ->get()
            ->pluck("total_price")
            ->toArray();

        $current_total_price = array_sum($currentOrdersPrice);

        foreach ($currentOrders as $order_id) {
            if (!in_array($order_id, $newOrders)) {
                JoPoOrders::where("id", $order_id)->forceDelete();
            }
        }

        $totalPriceSum = 0;

        foreach ($orders as $index => $values) {
            $newTotalPrice = $values["quantity"] * $values["price"];
            JoPoOrders::withTrashed()->updateOrCreate(
                [
                    "id" => $values["id"] ?? null,
                ],
                [
                    "item_id" => $values["item_id"],
                    "uom_id" => $values["uom_id"],
                    "price" => $values["price"],
                    "quantity" => $values["quantity"],
                    "total_price" => $newTotalPrice,
                    "attachment" => $values["attachment"],
                    "remarks" => $values["remarks"],
                    "asset" => $values["asset"],
                    "asset_code" => $values["asset_code"],
                ]
            );
            $totalPriceSum += $newTotalPrice;
        }

        $activityDescription = "Job order purchase request ID: {$id} has been updated by UID: {$user_id}";

        if (!empty($updatedItems)) {
            $activityDescription .= ". Price updates: ";
            foreach ($updatedItems as $item) {
                $activityDescription .=
                    "Item ID {$item["id"]}: " .
                    "{$item["name"]} {$item["old_price"]} -> {$item["new_price"]}, " .
                    "Total price {$item["old_total_price"]} -> {$item["new_total_price"]}, ";
            }
            $activityDescription = rtrim($activityDescription, ", ");
        }

        if (!empty($buyerTaggingDetails)) {
            $activityDescription .=
                ". Buyer Tagging: " . implode(", ", $buyerTaggingDetails);
        }

        if (!empty($buyerChangeDetails)) {
            $activityDescription .=
                ". Buyer Changes: " . implode(", ", $buyerChangeDetails);
        }

        LogHistory::create([
            "activity" => $activityDescription,
            "jo_po_id" => $id,
            "action_by" => $user_id,
        ]);

        if ($totalPriceSum > $current_total_price) {
            foreach ($po_approvers as $po_approver) {
                $po_approver->forceDelete();
            }

            $charging_purchase_order_setting_id = GlobalFunction::job_request_purchase_order_charger_setting_id(
                $request->company_id,
                $request->business_unit_id,
                $request->department_id
            );

            $charging_po_approvers = JobOrderPurchaseOrderApprovers::where(
                "jo_purchase_order_id",
                $charging_purchase_order_setting_id->id
            )->get();

            $fixed_charging_approvers = $charging_po_approvers->take(2);
            $price_based_charging_approvers = $charging_po_approvers
                ->slice(2)
                ->filter(function ($approver) use ($sumOfTotalPrices) {
                    return $approver->base_price <= $sumOfTotalPrices;
                })
                ->sortBy("base_price");
            $final_charging_approvers = $fixed_charging_approvers->concat(
                $price_based_charging_approvers
            );

            $layer_count = 1;

            foreach ($final_charging_approvers as $approver) {
                JoPoHistory::create([
                    "jo_po_id" => $job_order->id,
                    "approver_type" => "charging",
                    "approver_id" => $approver->approver_id,
                    "approver_name" => $approver->approver_name,
                    "layer" => $layer_count++,
                ]);
            }
        }

        $poTransaction = $job_order->fresh([
            "jo_po_orders",
            "jo_approver_history",
        ]);

        new JoPoResource($poTransaction);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_UPDATE,
            $poTransaction
        );
    }

    public function update_po_to_direct_po(Request $request, $id)
    {
        $user_id = Auth()->user()->id;
        $requestor_deptartment_id = Auth()->user()->department_id;
        $requestor_department_unit_id = Auth()->user()->department_unit_id;
        $requestor_company_id = Auth()->user()->company_id;
        $requestor_business_id = Auth()->user()->business_unit_id;
        $requestor_location_id = Auth()->user()->location_id;
        $requestor_sub_unit_id = Auth()->user()->sub_unit_id;

        $dateToday = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $orders = $request->order;
        $newTotalPrice = array_sum(array_column($orders, "total_price"));

        $requestor_setting_id = GlobalFunction::job_request_requestor_setting_id(
            $requestor_company_id,
            $requestor_business_id,
            $requestor_deptartment_id,
            $requestor_department_unit_id,
            $requestor_sub_unit_id,
            $requestor_location_id
        );

        $charging_setting_id = GlobalFunction::job_request_charger_setting_id(
            $request->company_id,
            $request->business_unit_id,
            $request->department_id,
            $request->department_unit_id,
            $request->sub_unit_id,
            $request->location_id
        );

        $requestor_approvers = JobOrderApprovers::where(
            "job_order_id",
            $requestor_setting_id->id
        )
            ->latest()
            ->get();

        $final_requestor_approvers = $requestor_approvers->take(2);

        $final_charging_approvers = collect();
        if ($requestor_setting_id->id !== $charging_setting_id->id) {
            $charging_approvers = JobOrderApprovers::where(
                "job_order_id",
                $charging_setting_id->id
            )
                ->latest()
                ->get();

            $final_charging_approvers = $charging_approvers->take(2);
        }

        $current_po = JOPOTransaction::where("id", $id)->first();

        if (!$current_po) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        // Get existing items before deletion
        $existingItems = JoPoOrders::where("jo_po_id", $id)->get();
        $updatedItems = [];

        JoPoHistory::where("jo_po_id", $current_po->id)->forceDelete();

        foreach ($orders as $index => $values) {
            $existingItem = $existingItems->firstWhere("id", $values["id"]);

            if ($existingItem) {
                $oldPrice = $existingItem->price;
                $newPrice = $values["price"];
                $oldTotalPrice = $existingItem->total_price;
                $newTotalPrice = $values["quantity"] * $values["price"];

                if (
                    $oldPrice != $newPrice ||
                    $oldTotalPrice != $newTotalPrice
                ) {
                    $updatedItems[] = [
                        "id" => $values["id"],
                        "old_price" => $oldPrice,
                        "new_price" => $newPrice,
                        "old_total_price" => $oldTotalPrice,
                        "new_total_price" => $newTotalPrice,
                    ];
                }
            }
        }

        $current_po->update([
            "po_description" => $request->po_description,
            "date_needed" => $request->date_needed,
            "user_id" => $request->user_id,
            "type_id" => $request->type_id,
            "type_name" => $request->type_name,
            "business_unit_id" => $request->business_unit_id,
            "business_unit_name" => $request->business_unit_name,
            "company_id" => $request->company_id,
            "company_name" => $request->company_name,
            "department_id" => $request->department_id,
            "department_name" => $request->department_name,
            "department_unit_id" => $request->department_unit_id,
            "department_unit_name" => $request->department_unit_name,
            "location_id" => $request->location_id,
            "location_name" => $request->location_name,
            "sub_unit_id" => $request->sub_unit_id,
            "sub_unit_name" => $request->sub_unit_name,
            "account_title_id" => $request->account_title_id,
            "account_title_name" => $request->account_title_name,
            "module_name" => $request->module_name,
            "total_item_price" => $newTotalPrice, // Using the calculated total price
            "supplier_id" => $request->supplier_id,
            "supplier_name" => $request->supplier_name,
            "status" => "Pending",
            "asset" => $request->asset,
            "sgp" => $request->sgp,
            "f1" => $request->f1,
            "f2" => $request->f2,
            "layer" => "1",
            "rush" => $request->rush,
            "outside_labor" => $request->outside_labor,
            "cap_ex" => $request->cap_ex,
            "direct_po" => $dateToday,
            "helpdesk_id" => $request->helpdesk_id,
            "cip_number" => $request->cip_number,
            "description" => $request->description,
        ]);

        // Handle order updates
        foreach ($orders as $index => $values) {
            $newTotalPrice = $values["quantity"] * $values["price"];
            JoPoOrders::withTrashed()->updateOrCreate(
                [
                    "id" => $values["id"] ?? null,
                ],
                [
                    "jo_po_id" => $current_po->id, // Added this line
                    "item_id" => $values["item_id"],
                    "uom_id" => $values["uom_id"],
                    "price" => $values["price"],
                    "quantity" => $values["quantity"],
                    "total_price" => $newTotalPrice,
                    "attachment" => $values["attachment"],
                    "remarks" => $values["remarks"],
                    "asset" => $values["asset"],
                    "asset_code" => $values["asset_code"],
                ]
            );
        }

        // Delete orders that are no longer present
        $newOrderIds = collect($orders)
            ->pluck("id")
            ->toArray();
        JoPoOrders::where("jo_po_id", $id)
            ->whereNotIn("id", $newOrderIds)
            ->forceDelete();

        $activityDescription = "Job order purchase request ID: {$id} has been updated by UID: {$user_id}";

        if (!empty($updatedItems)) {
            $activityDescription .= ". Price updates: ";
            foreach ($updatedItems as $item) {
                $activityDescription .=
                    "Item ID {$item["id"]}: " .
                    "Price {$item["old_price"]} -> {$item["new_price"]}, " .
                    "Total price {$item["old_total_price"]} -> {$item["new_total_price"]}, ";
            }
            $activityDescription = rtrim($activityDescription, ", ");
        }

        LogHistory::create([
            "activity" => $activityDescription,
            "jo_po_id" => $id,
            "action_by" => $user_id,
        ]);

        $layer_count = 1;

        if ($requestor_setting_id->id === $charging_setting_id->id) {
            foreach ($final_requestor_approvers as $approver) {
                JoPoHistory::create([
                    "jo_po_id" => $current_po->id, // Fixed variable name
                    "approver_type" => "service provider",
                    "approver_id" => $approver->approver_id,
                    "approver_name" => $approver->approver_name,
                    "layer" => $layer_count++,
                ]);
            }
        } else {
            foreach ($final_requestor_approvers as $approver) {
                JoPoHistory::create([
                    "jo_po_id" => $current_po->id, // Fixed variable name
                    "approver_type" => "service provider",
                    "approver_id" => $approver->approver_id,
                    "approver_name" => $approver->approver_name,
                    "layer" => $layer_count++,
                ]);
            }

            foreach ($final_charging_approvers as $approver) {
                JoPoHistory::create([
                    "jo_po_id" => $current_po->id, // Fixed variable name
                    "approver_type" => "charging",
                    "approver_id" => $approver->approver_id,
                    "approver_name" => $approver->approver_name,
                    "layer" => $layer_count++,
                ]);
            }
        }

        $poTransaction = $current_po->fresh([
            "jo_po_orders",
            "jo_approver_history",
        ]);

        new JoPoResource($poTransaction);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_UPDATE,
            $poTransaction
        );
    }

    public function update_price(Request $request, $id)
    {
        $user_id = Auth()->user()->id;
        $job_order = JOPOTransaction::where("id", $id)
            ->get()
            ->first();

        $oldTotalPrice = $job_order->total_item_price;
        $not_found = JOPOTransaction::where("id", $id)->exists();

        $po_approvers = $job_order->jo_approver_history()->get();

        if (!$not_found) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        $orders = $request->order;
        $newOrders = collect($orders)
            ->pluck("id")
            ->toArray();
        $currentOrders = JoPoOrders::where("jo_po_id", $newOrders)
            ->get()
            ->pluck("id")
            ->toArray();

        foreach ($currentOrders as $order_id) {
            if (!in_array($order_id, $newOrders)) {
                JoPoOrders::where("id", $order_id)->forceDelete();
            }
        }

        $updatedItems = [];
        $totalPriceSum = 0;
        $oldSupplier = $job_order->supplier_name;
        $newSupplier = $request["supplier_name"];

        foreach ($orders as $values) {
            $order_id = $values["id"];
            $poItem = JoPoOrders::where("id", $order_id)->first();

            if ($poItem) {
                $item_name = $poItem->description;
                $oldPrice = $poItem->unit_price;
                $newPrice = $values["price"];
                $oldTotalPrice = $poItem->total_price;
                $newTotalPrice = $poItem->quantity * $values["price"];
                $poItem->update([
                    "unit_price" => $values["price"],
                    "total_price" => $newTotalPrice,
                ]);
                $totalPriceSum += $newTotalPrice;

                $updatedItems[] = [
                    "id" => $order_id,
                    "name" => $item_name,
                    "old_price" => $oldPrice,
                    "new_price" => $newPrice,
                    "old_total_price" => $oldTotalPrice,
                    "new_total_price" => $newTotalPrice,
                ];
            }
        }

        $activityDescription =
            "Job order purchase order ID: " .
            $job_order->id .
            " has been updated by UID: " .
            $user_id .
            ". Price updates: ";
        foreach ($updatedItems as $item) {
            $activityDescription .=
                "Item ID {$item["id"]}: " .
                "{$item["name"]} {$item["old_price"]} -> {$item["new_price"]}, " .
                "Total price {$item["old_total_price"]} -> {$item["new_total_price"]}, ";
        }
        $activityDescription = rtrim($activityDescription, ", ");

        if ($oldSupplier !== $newSupplier) {
            $activityDescription .= ". Supplier changed from $oldSupplier to $newSupplier";
        }

        $activityDescription .= ".";

        LogHistory::create([
            "activity" => $activityDescription,
            "jo_po_id" => $id,
            "action_by" => $user_id,
        ]);

        $oldTotalPrice = $job_order->total_item_price;

        if ($totalPriceSum > $oldTotalPrice) {
            foreach ($po_approvers as $po_approver) {
                $po_approver->update([
                    "approved_at" => null,
                    "rejected_at" => null,
                ]);
            }
        }

        $po_settings = POSettings::where("company_id", $job_order->company_id)
            ->get()
            ->first();

        $highestPriceRange = PoApprovers::max("price_range");

        $updateData = [
            "total_item_price" => $totalPriceSum,
            "status" => "Pending",
            "layer" => "1",
            "updated_by" => $user_id,
            "edit_remarks" => $request->edit_remarks,
            "supplier_id" => $request->supplier_id,
            "supplier_name" => $request->supplier_name,
            "cap_ex" => $request->cap_ex,
            "outside_labor" => $request->outside_labor,
        ];

        if ($request->boolean("returned_po")) {
            $updateData["status"] = "pending";
        }

        $job_order->update($updateData);

        if ($totalPriceSum >= $highestPriceRange) {
            $approvers = PoApprovers::where(
                "price_range",
                ">=",
                $highestPriceRange
            )
                ->where("po_settings_id", $po_settings->company_id)
                ->get();
            $po_approver_history = $job_order->jo_approver_history()->first();

            foreach ($approvers as $index) {
                $existing_approver = JoPoHistory::where(
                    "jo_po_id",
                    $po_approver_history->jo_po_id
                )
                    ->where("approver_id", $index["approver_id"])
                    ->first();

                if (!$existing_approver) {
                    JoPoHistory::create([
                        "jo_po_id" => $po_approver_history->jo_po_id,
                        "approver_id" => $index["approver_id"],
                        "approver_name" => $index["approver_name"],
                        "layer" => $index["layer"],
                    ]);
                }
            }
        }

        $poTransaction = $job_order->fresh([
            "jo_po_orders",
            "jo_approver_history",
        ]);

        new JoPoResource($poTransaction);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_UPDATE,
            $poTransaction
        );
    }

    public function tagging_badge(Request $request)
    {
        $user_id = Auth()->user()->id;
        $status = $request->status;
        $for_tagging = PurchaseAssistant::query()
            ->with([
                "approver_history",
                "log_history" => function ($query) {
                    $query->orderBy("created_at", "desc");
                },
                "po_transaction.order",
                "po_transaction.approver_history",
                "order" => function ($query) {
                    $query->whereNull("buyer_id");
                },
            ])
            ->where("status", "Approved")
            ->whereNull("for_po_only")
            ->whereNull("rejected_at")
            ->whereNull("voided_at")
            ->whereNull("cancelled_at")
            ->where(function ($query) {
                $query
                    ->where("module_name", "!=", "Asset")
                    ->orWhere(function ($query) {
                        $query
                            ->where("module_name", "Asset")
                            ->where(function ($query) {
                                $query
                                    ->whereHas("approver_history", function (
                                        $query
                                    ) {
                                        $query->whereNotNull("approved_at");
                                    })
                                    ->orWhereDoesntHave("approver_history");
                            });
                    });
            })
            ->whereHas("order", function ($query) {
                $query
                    ->where(function ($subQuery) {
                        $subQuery
                            ->whereNull("buyer_id")
                            ->orWhereNull("buyer_name");
                    })
                    ->whereNull("po_at");
            })
            ->count();

        $for_po = PurchaseAssistant::query()
            ->where("status", "Approved")
            ->with([
                "order" => function ($query) {
                    $query->whereNull("buyer_id")->whereNull("supplier_id");
                },
            ])
            ->whereHas("order", function ($query) {
                $query->whereNull("buyer_id")->whereNull("supplier_id");
            })
            ->whereNotNull("for_po_only")
            ->whereNull("rejected_at")
            ->whereNull("voided_at")
            ->whereNull("cancelled_at")
            ->count();

        $tagged_buyer = PurchaseAssistant::query()
            ->with("order")
            ->withCount([
                "order" => function ($query) {
                    $query->whereNotNull("buyer_id")->whereNull("po_at");
                },
            ])
            ->where(function ($query) {
                $query->whereHas("order", function ($subQuery) {
                    $subQuery->whereNotNull("buyer_id")->whereNull("po_at");
                });
            })
            ->where("status", "Approved")
            ->whereNull("cancelled_at")
            ->count();

        $pending_po_count = PurchaseAssistant::withCount([
            "po_transaction" => function ($query) {
                $query
                    ->whereIn("status", ["Pending", "For Approval"])
                    ->whereNull("deleted_at")
                    ->whereNull("cancelled_at");
            },
        ])
            ->get()
            ->sum("po_transaction_count");

        $approved = PurchaseAssistant::withCount([
            "po_transaction" => function ($query) {
                $query
                    ->whereNull("rejected_at")
                    ->whereNull("voided_at")
                    ->whereNull("cancelled_at")
                    ->where("status", "For Receiving");
            },
        ])
            ->whereNull("rejected_at")
            ->whereNull("voided_at")
            ->whereNull("cancelled_at")
            ->get()
            ->sum("po_transaction_count");

        $rejected = PurchaseAssistant::where(function ($query) {
            $query
                ->where(function ($subQuery) {
                    $subQuery
                        ->whereHas("po_transaction", function ($poQuery) {
                            $poQuery
                                ->where("module_name", "!=", "Asset")
                                ->where("status", "Reject")
                                ->whereNotNull("rejected_at");
                        })
                        ->whereHas("order", function ($orderQuery) {
                            $orderQuery->whereNotNull("buyer_id");
                        });
                })
                ->orWhere(function ($subQuery) {
                    $subQuery
                        ->whereHas("po_transaction", function ($poQuery) {
                            $poQuery
                                ->where("module_name", "Asset")
                                ->where("status", "Reject")
                                ->whereNotNull("rejected_at");
                        })
                        ->whereDoesntHave("order", function ($orderQuery) {
                            $orderQuery->whereNotNull("buyer_id");
                        });
                });
        })
            ->withCount([
                "po_transaction" => function ($query) {
                    $query
                        ->where("status", "Reject")
                        ->whereNotNull("rejected_at");
                },
            ])
            ->get()
            ->sum("po_transaction_count");

        $return_po = PurchaseAssistantPO::where("status", "Return")->count();

        $cancel = PurchaseAssistant::withCount([
            "po_transaction" => function ($query) {
                $query
                    ->where("status", "Cancelled")
                    ->whereNotNull("cancelled_at")
                    ->withTrashed();
            },
        ])
            ->get()
            ->sum("po_transaction_count");

        $for_job = JobOrderTransactionPA::where("status", "Approved")
            ->with("order", function ($query) {
                $query->whereNull("po_at");
            })
            ->whereHas("order", function ($query) {
                $query->whereNull("po_at");
            })
            ->whereNull("cancelled_at")
            ->whereNull("voided_at")
            ->whereNotNull("approved_at")
            ->count();

        $for_po_approval = JOPOTransaction::where("status", "Pending")
            ->orWhere("status", "For Approval")
            ->whereNull("cancelled_at")
            ->whereNull("rejected_at")
            ->whereNull("voided_at")
            ->count();

        $po_rejected = JOPOTransaction::where("status", "Reject")
            ->whereNotNull("rejected_at")
            ->count();

        $result = [
            "for_tagging" => $for_tagging,
            "for_po_only" => $for_po,
            "tagged_request" => $tagged_buyer,
            "for_approval" => $pending_po_count,
            "approved" => $approved,
            "rejected" => $rejected,
            "return_po" => $return_po,
            "cancel_po" => $cancel,
            "job_order" => [
                "jo_for_po" => $for_job,
                "for_po_approval" => $for_po_approval,
                "po_rejected" => $po_rejected,
            ],
        ];

        return GlobalFunction::responseFunction(
            Message::DISPLAY_COUNT,
            $result
        );
    }

    public function return_pr(PORequest $request, $id)
    {
        $user_id = Auth()->user()->id;
        $reason = $request->reason;
        $pr_transaction = PRTransaction::where("id", $id)->first();

        $approvers = $pr_transaction->approver_history->pluck("id")->toArray();

        if (!$pr_transaction) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $activityDescription =
            "Purchase request '.$pr_transaction->pr_year_number_id.' has been returned by UID: " .
            $user_id .
            " Reason: " .
            $reason;

        PrHistory::whereIn("id", $approvers)->update([
            "approved_at" => null,
            "rejected_at" => null,
        ]);

        LogHistory::create([
            "activity" => $activityDescription,
            "pr_id" => $id,
            "action_by" => $user_id,
        ]);

        $pr_transaction->update([
            "status" => "Return",
            "reason" => $reason,
        ]);

        new JoPoResource($pr_transaction);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_UPDATE,
            $pr_transaction
        );
    }

    public function edit_unit_price(Request $request, $id)
    {
        $unit_price = $request->unit_price;
        $jr_item = JobItems::where("id", $id)
            ->get()
            ->first();

        if (!$jr_item) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $latest_total = $jr_item->quantity * $unit_price;

        $jr_item->update([
            "unit_price" => $unit_price,
            "total_price" => $latest_total,
        ]);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_UPDATE,
            $jr_item
        );
    }

    public function return_jo_po(UpdateRequest $request, $id)
    {
        $reason = $request->reason;
        $purchasing_assistant = Auth()->user()->id;
        $jo_po_transaction = JOPOTransaction::where("id", $id)->first();

        if (!$jo_po_transaction) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $direct_jo = $jo_po_transaction->direct_po;

        if ($direct_jo) {
            $jr_transaction = JobOrderTransaction::where(
                "id",
                $jo_po_transaction->jo_number
            )->first();

            $jr_transaction->update([
                "status" => "Return",
                "reason" => $reason,
            ]);
        }

        $jo_po = $jo_po_transaction->update([
            "status" => "Return",
            "reason" => $reason,
        ]);

        $activityDescription =
            "Purchase Order ID: " .
            $id .
            " has been returned by UID: " .
            $purchasing_assistant .
            " Reason: " .
            $reason;

        LogHistory::create([
            "activity" => $activityDescription,
            "jo_po_id" => $id,
            "action_by" => $purchasing_assistant,
        ]);

        $jo_po_transaction->jo_approver_history()->update([
            "approved_at" => null,
        ]);

        new JoPoResource($jo_po_transaction);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_RETURN,
            $jo_po_transaction
        );
    }

    public function resubmit_pr_asset(Request $request, $id)
    {
        $vlad_user = $request->v_name;
        $rdf_id = $request->rdf_id;

        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $user = $rdf_id . " (" . $vlad_user . ")";

        $pr_transaction = PRTransaction::where("pr_number", $id)
            ->where("status", "Return")
            ->first();

        if (!$pr_transaction) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $type_id = Type::where("name", "Asset")
            ->get()
            ->first();

        $pr_transaction->update([
            "pr_description" => $request["pr_description"],
            "date_needed" => $request["date_needed"],
            "type_id" => $type_id->id,
            "type_name" => $request->type_name,
            "business_unit_id" => $request->business_unit_id,
            "business_unit_name" => $request->business_unit_name,
            "company_id" => $request->company_id,
            "company_name" => $request->company_name,
            "department_id" => $request->department_id,
            "department_name" => $request->department_name,
            "department_unit_id" => $request->department_unit_id,
            "department_unit_name" => $request->department_unit_name,
            "location_id" => $request->location_id,
            "location_name" => $request->location_name,
            "sub_unit_id" => $request->sub_unit_id,
            "sub_unit_name" => $request->sub_unit_name,
            "account_title_id" => $request->account_title_id,
            "account_title_name" => $request->account_title_name,
            "module_name" => $type_id->name,
            "status" => "Approved",
            "asset" => $request->asset,
            "sgp" => $request->sgp,
            "f1" => $request->f1,
            "f2" => $request->f2,
            "vrid" => $request->vrid,
            "approved_at" => $date_today,
            "layer" => "1",
            "description" => $request->description,
        ]);
        $pr_transaction->save();

        $orders = $request->order;

        $newOrders = collect($orders)
            ->pluck("id")
            ->toArray();

        $currentOrders = $pr_transaction
            ->order()
            ->get()
            ->pluck("id")
            ->toArray();

        foreach ($currentOrders as $order_id) {
            if (!in_array($order_id, $newOrders)) {
                PRItems::where("id", $order_id)->forceDelete();
            }
        }

        foreach ($orders as $index => $values) {
            PRItems::withTrashed()->updateOrCreate(
                [
                    "id" => $values["id"] ?? null,
                ],
                [
                    "transaction_id" => $pr_transaction->id,
                    "item_code" => $values["item_code"],
                    "item_name" => $values["item_name"],
                    "uom_id" => $values["uom_id"],
                    "quantity" => $values["quantity"],
                    "remarks" => $values["remarks"],
                ]
            );
        }

        $activityDescription = "Purchase Request ID: {$pr_transaction->id} has been resubmit by UID: {$user}.";

        LogHistory::create([
            "activity" => $activityDescription,
            "rr_id" => $pr_transaction->id,
        ]);

        $collect = new PRTransactionResource($pr_transaction);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_UPDATE,
            $collect
        );
    }

    public function update_buyer(Request $request, $id)
    {
        $user_id = Auth()->user()->id;
        $purchase_order = POTransaction::with("order")->find($id);

        if ($purchase_order->cancelled_at) {
            return GlobalFunction::invalid(Message::PO_CANCELLED_ALREADY);
        }

        $payload = $request->all();
        $payload_items = $payload["items"] ?? $payload;

        $item_ids = array_column($payload_items, "id");

        $po_items = POItems::whereIn("id", $item_ids)
            ->where("po_id", $id)
            ->get();

        if ($po_items->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $item_details = [];
        foreach ($po_items as $item) {
            $payloadItem = collect($payload_items)->firstWhere("id", $item->id);
            $old_buyer = $item->buyer_name;
            $old_buyer_id = $item->buyer_id;

            $item->update([
                "buyer_id" => $payloadItem["buyer_id"],
                "buyer_name" => $payloadItem["buyer_name"],
            ]);

            $item_details[] = "Item ID {$item->id}: Buyer reassigned from {$old_buyer} (ID: {$old_buyer_id}) to {$payloadItem["buyer_name"]} (ID: {$payloadItem["buyer_id"]})";
        }

        $item_details_string = implode(", ", $item_details);

        $activityDescription =
            "Purchase order ID: $id - has been updated to buyer for " .
            count($item_details) .
            " item(s). Details: " .
            $item_details_string;

        LogHistory::create([
            "activity" => $activityDescription,
            "po_id" => $id,
            "action_by" => $user_id,
        ]);

        return GlobalFunction::responseFunction(
            Message::BUYER_UPDATED,
            $po_items
        );
    }
}
