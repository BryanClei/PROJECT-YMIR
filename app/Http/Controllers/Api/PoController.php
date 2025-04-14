<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use App\Models\POItems;
use App\Models\PRItems;
use App\Models\JobItems;
use App\Models\PoHistory;
use App\Response\Message;
use App\Models\JobHistory;
use App\Models\JoPoOrders;
use App\Models\LogHistory;
use App\Models\POSettings;
use App\Models\JoPoHistory;
use App\Models\PoApprovers;
use Illuminate\Http\Request;
use App\Models\POTransaction;
use App\Models\PRTransaction;
use App\Models\JOPOTransaction;
use App\Functions\GlobalFunction;
use App\Http\Resources\PoResource;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\PO\PORequest;
use App\Models\JobOrderTransaction;
use App\Http\Controllers\Controller;
use App\Http\Requests\PRViewRequest;
use App\Http\Resources\JoPoResource;
use App\Helpers\BadgeHelperFunctions;
use App\Http\Resources\PoItemResource;
use App\Http\Requests\PO\UpdateRequest;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\JoPo\StoreRequest;
use App\Http\Requests\PO\ValidationRequest;
use App\Models\JobOrderPurchaseOrderApprovers;

class PoController extends Controller
{
    public function index(PRViewRequest $request)
    {
        $status = $request->status;
        $user_id = Auth()->user()->id;

        $purchase_order = POTransaction::orderBy("rush", "desc")
            ->orderBy("updated_at", "desc")
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

    public function view(Request $request, $id)
    {
        $status = $request->status;
        $user_id = Auth()->user()->id;

        $purchase_order = POTransaction::where("id", $id)
            ->orderByDesc("updated_at")
            ->get()
            ->first();

        if (!$purchase_order) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        new PoResource($purchase_order);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $purchase_order
        );
    }

    public function approved_pr(Request $request)
    {
        $status = $request->status;
        $user_id = Auth()->user()->id;

        $purchase_order = PRTransaction::with("order", "approver_history")
            ->orderByDesc("updated_at")
            ->whereNotNull("approved_at")
            ->where("status", "Approved")
            ->get();

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

    public function store(ValidationRequest $request)
    {
        $user_id = Auth()->user()->id;

        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $user_tagged = $request->boolean("user_tagging")
            ? Carbon::now()
                ->timeZone("Asia/Manila")
                ->format("Y-m-d H:i")
            : null;

        $orders = $request->order;

        $sumOfTotalPrices = array_sum(array_column($orders, "total_price"));

        $if_exist = PRTransaction::when(
            $request->module_name === "Asset",
            function ($query) use ($request) {
                return $query->where("pr_number", $request->pr_number);
            },
            function ($query) use ($request) {
                return $query->where("id", $request->pr_number);
            }
        )->get();

        if ($if_exist->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $po_settings = POSettings::where("company_id", $request->company_id)
            ->where("business_unit_id", $request->business_unit_id)
            ->where("department_id", $request->department_id)
            ->get()
            ->first();

        if (!$po_settings) {
            return GlobalFunction::notFound(Message::NO_APPROVERS_SETTINGS_YET);
        }

        $approvers_exist = PoApprovers::where(
            "po_settings_id",
            $po_settings->id
        )->get();

        if ($approvers_exist->isEmpty()) {
            return GlobalFunction::notFound(Message::NO_APPROVERS);
        }

        $check_price_approvers = PoApprovers::where(
            "price_range",
            "<=",
            $sumOfTotalPrices
        )
            ->where("po_settings_id", $po_settings->id)
            ->get();

        if ($check_price_approvers->isEmpty()) {
            return GlobalFunction::notFound(Message::NO_APPROVERS_PRICE);
        }

        $current_year = date("Y");
        $latest_po = POTransaction::withTrashed()
            ->where("po_year_number_id", "like", $current_year . "-PO-%")
            ->orderByRaw(
                "CAST(SUBSTRING_INDEX(po_year_number_id, '-', -1) AS UNSIGNED) DESC"
            )
            ->first();

        $new_number = $latest_po
            ? (int) explode("-", $latest_po->po_year_number_id)[2] + 1
            : 1;

        $latest_po_number = POTransaction::withTrashed()->max("id") ?? 0;
        $po_number = $latest_po_number + 1;

        $po_year_number_id =
            $current_year . "-PO-" . str_pad($new_number, 3, "0", STR_PAD_LEFT);

        $purchase_order = new POTransaction([
            "po_year_number_id" => $po_year_number_id,
            "po_number" => $po_number,
            "pr_number" => $request->pr_number,
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
            "rush" => $request->rush,
            "layer" => "1",
            "cap_ex" => $request->cap_ex,
            "description" => $request->po_description,
            "user_tagging" => $user_tagged,
        ]);
        $purchase_order->save();

        foreach ($orders as $index => $values) {
            $remarks = $request["order"][$index]["remarks"];

            $attachments = $request["order"][$index]["attachment"];
            $filenames = [];
            if (!empty($attachments)) {
                foreach ($attachments as $fileIndex => $file) {
                    $originalFilename = basename($file);
                    $info = pathinfo($originalFilename);
                    $filenameOnly = $info["filename"];
                    $extension = $info["extension"];
                    $filename = "{$filenameOnly}_po_id_{$purchase_order->id}_item_{$index}_file_{$fileIndex}.{$extension}";
                    $filenames[] = $filename;
                }
            }

            POItems::create([
                "po_id" => $purchase_order->id,
                "pr_id" => $purchase_order->pr_number,
                "pr_item_id" => $request["order"][$index]["pr_item_id"],
                "reference_no" => $request["order"][$index]["reference_no"],
                "item_id" => $request["order"][$index]["item_id"],
                "item_code" => $request["order"][$index]["item_code"],
                "item_name" => $request["order"][$index]["item_name"],
                "uom_id" => $request["order"][$index]["uom_id"],
                "supplier_id" => $request["order"][$index]["supplier_id"],
                "price" => $request["order"][$index]["price"],
                // "item_stock" => $request["order"][$index]["item_stock"],
                "quantity" => $request["order"][$index]["quantity"],
                "total_price" =>
                    $request["order"][$index]["price"] *
                    $request["order"][$index]["quantity"],
                "quantity_serve" => 0,
                "attachment" => json_encode($filenames),
                "buyer_id" => $request["order"][$index]["buyer_id"],
                "buyer_name" => $request["order"][$index]["buyer_name"],
                "remarks" => $remarks,
                "warehouse_id" => $request["order"][$index]["warehouse_id"],
                "category_id" => $request["order"][$index]["category_id"],
            ]);
        }

        foreach ($orders as $index => $values) {
            $item_id = $request["order"][$index]["id"];
            $items = PRItems::where("id", $item_id)->update([
                "po_at" => $date_today,
                "purchase_order_id" => $purchase_order->id,
                "supplier_id" => $request["order"][$index]["supplier_id"],
            ]);
        }

        $activityDescription =
            "Purchase order ID:" .
            $purchase_order->id .
            " has been created by UID: " .
            $user_id;

        LogHistory::create([
            "activity" => $activityDescription,
            "po_id" => $purchase_order->id,
            "action_by" => $user_id,
        ]);

        // $purchase_items = POItems::where("po_id", $purchase_order->id)
        //     ->get()
        //     ->pluck("total_price")
        //     ->toArray();

        // $sum = array_sum($purchase_items);

        // $approvers = PoApprovers::where("price_range", "<=", $sum)
        //     ->where("po_settings_id", $po_settings->id)
        //     ->get();

        foreach ($check_price_approvers as $index) {
            PoHistory::create([
                "po_id" => $purchase_order->id,
                "approver_id" => $index["approver_id"],
                "approver_name" => $index["approver_name"],
                "layer" => $index["layer"],
            ]);
        }

        $po_collect = new PoResource($purchase_order);

        return GlobalFunction::save(Message::PURCHASE_ORDER_SAVE, $po_collect);
    }

    public function store_jo(StoreRequest $request)
    {
        $user_id = Auth()->user()->id;
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $orders = $request->order;

        $sumOfTotalPrices = array_sum(array_column($orders, "total_price"));

        $jr_orders = JobOrderTransaction::where(
            "id",
            $request->jo_number
        )->first();

        $job_request_order = $jr_orders->order;

        $charging_purchase_order_setting_id = GlobalFunction::job_request_purchase_order_charger_setting_id(
            $request->company_id,
            $request->business_unit_id,
            $request->department_id
        );

        $charging_po_approvers = JobOrderPurchaseOrderApprovers::where(
            "jo_purchase_order_id",
            $charging_purchase_order_setting_id->id
        )->get();

        if (!$charging_po_approvers) {
            return GlobalFunction::notFound(Message::NO_APPROVERS_SETTINGS_YET);
        }

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

        $current_year = date("Y");
        $latest_po = JOPOTransaction::withTrashed()
            ->where("po_year_number_id", "like", $current_year . "-JO-%")
            ->orderByRaw(
                "CAST(SUBSTRING_INDEX(po_year_number_id, '-', -1) AS UNSIGNED) DESC"
            )
            ->first();

        $new_number = $latest_po
            ? (int) explode("-", $latest_po->po_year_number_id)[2] + 1
            : 1;

        $po_year_number_id =
            $current_year . "-JO-" . str_pad($new_number, 3, "0", STR_PAD_LEFT);

        $latest_po_number =
            JOPOTransaction::withTrashed()->max("po_number") ?? 0;
        $increment = $latest_po_number + 1;

        $job_order = new JOPOTransaction([
            "po_year_number_id" => $po_year_number_id,
            "po_number" => $increment,
            "jo_number" => $request->jo_number,
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
            "rush" => $request->rush,
            "outside_labor" => $request->outside_labor,
            "cap_ex" => $request->cap_ex,
            "direct_po" => $request->direct_po,
            "helpdesk_id" => $request->helpdesk_id,
            "cip_number" => $request->cip_number,
            "layer" => "1",
            "description" => $request->description,
        ]);
        $job_order->save();

        foreach ($orders as $index => $values) {
            JoPoOrders::create([
                "jo_po_id" => $job_order->id,
                "jo_transaction_id" => $job_order->jo_number,
                "jo_item_id" => $request["order"][$index]["id"],
                "description" => $request["order"][$index]["description"],
                "uom_id" => $request["order"][$index]["uom_id"],
                "unit_price" => $request["order"][$index]["price"],
                "quantity" => $request["order"][$index]["quantity"],
                "quantity_serve" => 0,
                "total_price" =>
                    $request["order"][$index]["price"] *
                    $request["order"][$index]["quantity"],
                "attachment" => $request["order"][$index]["attachment"],
                "remarks" => $request["order"][$index]["remarks"],
                "asset" => $request["order"][$index]["asset"],
                "asset_code" => $request["order"][$index]["asset_code"],
                "helpdesk_id" => $request["order"][$index]["helpdesk_id"],
                "buyer_id" => $request["order"][$index]["buyer_id"],
                "buyer_name" => $request["order"][$index]["buyer_name"],
            ]);
        }

        foreach ($orders as $index => $values) {
            $item_id = $request["order"][$index]["id"];
            $items = JobItems::where("id", $item_id)->update([
                "po_at" => $date_today,
                "purchase_order_id" => $job_order->id,
            ]);
        }

        $updatedItems = [];
        foreach ($orders as $index => $values) {
            $originalItem = collect($job_request_order)->firstWhere(
                "id",
                $values["id"]
            );

            if ($originalItem) {
                $oldUnitPrice =
                    $originalItem["price"] ?? $originalItem["unit_price"];
                $newUnitPrice = $values["price"] ?? $values["unit_price"];

                $oldTotalPrice =
                    $originalItem["total_price"] ??
                    $oldUnitPrice * $originalItem["quantity"];
                $newTotalPrice =
                    $values["total_price"] ??
                    $newUnitPrice * $values["quantity"];
                $item_name = $originalItem["description"];

                if (
                    $oldUnitPrice != $newUnitPrice ||
                    $oldTotalPrice != $newTotalPrice
                ) {
                    $updatedItems[] = [
                        "id" => $values["id"],
                        "name" => $item_name,
                        "old_unit_price" => $oldUnitPrice,
                        "new_unit_price" => $newUnitPrice,
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
                        $buyerChangeDetails[] = "Item ID {$values["id"]}: Buyer reassigned from {$oldBuyerName} (ID: {$oldBuyerId}) to {$newBuyerName} (ID: {$newBuyerId})";
                    }
                }
            }
        }

        $activityDescription =
            "Job order purchase order ID: " .
            $job_order->id .
            " has been created by UID: " .
            $user_id;

        if (!empty($updatedItems)) {
            $activityDescription .= ". Price updates: ";
            foreach ($updatedItems as $item) {
                $activityDescription .=
                    "Item ID {$item["id"]}: " .
                    "{$item["name"]} {$item["old_unit_price"]} -> {$item["new_unit_price"]}, " .
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
            "jo_po_id" => $job_order->id,
            "action_by" => $user_id,
        ]);

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

        $jo_collect = new JoPoResource($job_order);

        return GlobalFunction::save(Message::JOB_ORDER_SAVE, $jo_collect);
    }

    public function update(UpdateRequest $request, $id)
    {
        $user_id = Auth()->user()->id;
        $purchase_order = POTransaction::find($id);

        $not_found = POTransaction::where("id", $id)->exists();

        if (!$not_found) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $user_tagged = $request->boolean("user_tagging")
            ? Carbon::now()
                ->timeZone("Asia/Manila")
                ->format("Y-m-d H:i")
            : null;

        $orders = $request->order;

        $purchase_order->update([
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
            "supplier_id" => $request->supplier_id,
            "supplier_name" => $request->supplier_name,
            "module_name" => $request->module_name,
            "status" => "Pending",
            "asset" => $request->asset,
            "sgp" => $request->sgp,
            "f1" => $request->f1,
            "f2" => $request->f2,
            "rush" => $request->rush,
            "layer" => "1",
            "cap_ex" => $request->cap_ex,
            "description" => $request->description,
            "user_tagging" => $user_tagged,
        ]);

        $activityDescription =
            "Purchase order ID:" .
            $id .
            " has been updated by UID: " .
            $user_id;

        LogHistory::create([
            "activity" => $activityDescription,
            "po_id" => $id,
            "action_by" => $user_id,
        ]);

        $newOrders = collect($orders)
            ->pluck("id")
            ->toArray();
        $currentOrders = POItems::where("po_id", $id)
            ->get()
            ->pluck("id")
            ->toArray();

        foreach ($currentOrders as $order_id) {
            if (!in_array($order_id, $newOrders)) {
                POItems::where("id", $order_id)->forceDelete();
            }
        }

        foreach ($orders as $index => $values) {
            $attachments = $values["attachment"];
            $filenames = [];
            if (!empty($attachments)) {
                foreach ($attachments as $fileIndex => $file) {
                    $originalFilename = basename($file);
                    $info = pathinfo($originalFilename);
                    $filenameOnly = $info["filename"];
                    $extension = $info["extension"];
                    $filename = "{$filenameOnly}_po_id_{$id}_item_{$index}_file_{$fileIndex}.{$extension}";
                    $filenames = $filename;
                }
            }

            POItems::withTrashed()->updateOrCreate(
                [
                    "id" => $values["id"] ?? null,
                ],
                [
                    "po_id" => $purchase_order->id,
                    "pr_id" => $purchase_order->pr_number,
                    "reference_no" => $value["reference_no"],
                    "item_id" => $values["item_id"],
                    "item_code" => $values["item_code"],
                    "item_name" => $values["item_name"],
                    "uom_id" => $values["uom_id"],
                    "supplier_id" => $values["supplier_id"],
                    "price" => $values["price"],
                    // "item_stock" => $values["item_stock"],
                    "quantity" => $values["quantity"],
                    "quantity_serve" => $values["quantity_serve"],
                    "total_price" => $values["total_price"],
                    "attachment" => json_encode($filenames),
                    "buyer_id" => $values["buyer_id"],
                    "buyer_name" => $values["buyer_name"],
                    "remarks" => $values["remarks"],
                    "warehouse_id" => $values["warehouse_id"],
                    "category_id" => $values["category_id"],
                ]
            );
        }

        $pr_collect = new PoResource($purchase_order);

        return GlobalFunction::save(
            Message::PURCHASE_ORDER_UPDATE,
            $pr_collect
        );
    }

    public function resubmit(Request $request, $id)
    {
        $purchase_order = POTransaction::find($id);
        $user_id = Auth()->user()->id;
        $not_found = POTransaction::where("id", $id)->exists();

        $po_approvers = $purchase_order->approver_history()->get();

        $orders = $request->order;

        if (!$not_found) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $purchase_order->update([
            "po_description" => $request->description,
            "description" => $request->description,
            "status" => "Pending",
            "rejected_at" => null,
            "reason" => $request->reason,
            "layer" => "1",
            // "supplier_id" => $request->supplier_id,
            // "supplier_name" => $request->supplier_name,
        ]);

        $activityDescription =
            "Purchase order ID: " .
            $id .
            " has been resubmitted by UID: " .
            $user_id;

        LogHistory::create([
            "activity" => $activityDescription,
            "po_id" => $id,
            "action_by" => $user_id,
        ]);

        $newOrders = collect($orders)
            ->pluck("id")
            ->toArray();
        $currentOrders = POItems::where("po_id", $id)
            ->get()
            ->pluck("id")
            ->toArray();

        foreach ($currentOrders as $order_id) {
            if (!in_array($order_id, $newOrders)) {
                POItems::where("id", $order_id)->forceDelete();
            }
        }

        $totalPriceSum = 0;

        foreach ($orders as $index => $values) {
            $newTotalPrice = $values["quantity"] * $values["price"];
            POItems::withTrashed()->updateOrCreate(
                [
                    "id" => $values["id"] ?? null,
                ],
                [
                    "po_id" => $purchase_order->id,
                    "pr_id" => $purchase_order->pr_number,
                    // "reference_no" => $values["reference_no"],
                    "item_id" => $values["item_id"],
                    "item_code" => $values["item_code"],
                    "item_name" => $values["item_name"],
                    "uom_id" => $values["uom_id"],
                    "supplier_id" => $values["supplier_id"],
                    "price" => $values["price"],
                    // "item_stock" => $values["item_stock"],
                    "quantity" => $values["quantity"],
                    "quantity_serve" => $values["quantity_serve"],
                    "total_price" => $newTotalPrice,
                    "attachment" => $values["attachment"],
                    "buyer_id" => $values["buyer_id"],
                    "buyer_name" => $values["buyer_name"],
                    "remarks" => $values["remarks"],
                    "warehouse_id" => $values["warehouse_id"],
                    "category_id" => $values["category_id"],
                ]
            );
            $totalPriceSum += $newTotalPrice;
        }

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
                ->where("po_settings_id", $po_settings->id)
                ->get();
            $po_approver_history = $purchase_order->approver_history()->first();

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
        } else {
            foreach ($po_approvers as $po_approver) {
                $po_approver->update([
                    "approved_at" => null,
                    "rejected_at" => null,
                ]);
            }
        }

        $po_collect = new PoResource($purchase_order);

        return GlobalFunction::save(Message::RESUBMITTED_PO, $po_collect);
    }

    public function destroy($id)
    {
        $purchase_order = POTransaction::where("id", $id)
            ->withTrashed()
            ->get();

        if ($purchase_order->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $purchase_order = POTransaction::withTrashed()->find($id);
        $is_active = POTransaction::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $purchase_order->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $purchase_order->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $purchase_order);
    }

    public function resubmit_jo(Request $request, $id)
    {
        $job_order = JOPOTransaction::find($id);
        $orders = $request->order;
        $user_id = auth()->user()->id;

        if (!$job_order) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $po_history = JoPoHistory::where("jo_po_id", $id)->get();
        if ($po_history->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $sumOfTotalPrices = array_sum(array_column($orders, "total_price"));

        $charging_setting_id = GlobalFunction::job_request_purchase_order_charger_setting_id(
            $request->company_id,
            $request->business_unit_id,
            $request->department_id
        );

        $po_approvers = JobOrderPurchaseOrderApprovers::where(
            "jo_purchase_order_id",
            $charging_setting_id->id
        )
            ->where("base_price", "<=", $sumOfTotalPrices)
            ->get();

        if ($po_approvers->isEmpty()) {
            return GlobalFunction::notFound(Message::NO_APPROVERS_PRICE);
        }

        // Reset approval history
        JoPoHistory::where("jo_po_id", $id)->delete();

        // Insert new approvers
        $layer = 1;
        foreach ($po_approvers as $approver) {
            JoPoHistory::create([
                "jo_po_id" => $id,
                "approver_id" => $approver->approver_id,
                "approver_name" => $approver->approver_name,
                "layer" => $layer++,
                "approved_at" => null,
            ]);
        }

        $job_order->update([
            "status" => "Pending",
            "rejected_at" => null,
            "reason" => null,
            "layer" => "1",
        ]);

        $updatedItems = [];
        foreach ($orders as $values) {
            $order_id = $values["id"];
            $poItem = JoPoOrders::where("id", $order_id)
                ->withTrashed()
                ->first();

            if ($poItem) {
                $oldPrice = $poItem->unit_price;
                $newPrice = $values["price"];
                $oldTotalPrice = $poItem->total_price;
                $newTotalPrice = $values["total_price"];

                $poItem->update([
                    "unit_price" => $newPrice,
                    "total_price" => $newTotalPrice,
                ]);

                if ($oldPrice != $newPrice) {
                    $updatedItems[] = [
                        "id" => $order_id,
                        "old_price" => $oldPrice,
                        "new_price" => $newPrice,
                        "old_total" => $oldTotalPrice,
                        "new_total" => $newTotalPrice,
                    ];
                }
            }
        }

        $activityDescription =
            "Job order purchase order ID: " .
            $id .
            " has been resubmitted by UID: " .
            $user_id;

        if (!empty($updatedItems)) {
            $activityDescription .= ". Price updates: ";
            foreach ($updatedItems as $item) {
                $activityDescription .=
                    "Item ID {$item["id"]}: Unit price {$item["old_price"]} -> {$item["new_price"]}, " .
                    "Total price {$item["old_total"]} -> {$item["new_total"]}, ";
            }
            $activityDescription = rtrim($activityDescription, ", ");
        }

        $activityDescription .= ".";

        LogHistory::create([
            "activity" => $activityDescription,
            "jo_po_id" => $id,
            "action_by" => $user_id,
        ]);

        $jo_collect = new JoPoResource($job_order);

        return GlobalFunction::save(Message::RESUBMITTED_PO, $jo_collect);
    }

    public function cancel_po(PORequest $request, $id)
    {
        $no_rr = $request->no_rr;
        $user = Auth()->user()->id;
        $po_cancel = POTransaction::where("id", $id)
            ->with("order")
            ->get()
            ->first();

        if (!$po_cancel) {
            return GlobalFunction::notFound(Message::INVALID_ACTION);
        }

        if ($po_cancel->status == "Cancelled") {
            return GlobalFunction::invalid(Message::PO_CANCELLED_ALREADY);
        }

        foreach ($po_cancel->order as $order) {
            $prItem = PRItems::find($order->pr_item_id);
            if ($prItem) {
                $partialReceived = $order->quantity_serve;
                $remainingQty = $order->quantity - $order->quantity_serve;

                $prItem->update([
                    "partial_received" => $partialReceived,
                    "remaining_qty" => $remainingQty,
                    "buyer_id" => null,
                    "buyer_name" => null,
                    "supplier_id" => null,
                    "po_at" => null,
                    "purchase_order_id" => null,
                ]);
            }
        }

        $po_cancel_order = $po_cancel
            ->order()
            ->pluck("pr_item_id")
            ->toArray();

        $pr_items = PRItems::whereIn("id", $po_cancel_order)->update([
            "buyer_id" => null,
            "buyer_name" => null,
            "supplier_id" => null,
            "po_at" => null,
            "purchase_order_id" => null,
        ]);

        $po_cancel->update([
            "reason" => $request->reason,
            "rejected_at" => null,
            "cancelled_at" => Carbon::now()
                ->timeZone("Asia/Manila")
                ->format("Y-m-d H:i"),
            "status" => "Cancelled",
        ]);

        $po_cancel->order()->delete();
        $po_cancel->delete();

        $activityDescription = $no_rr
            ? "Purchase order ID:" .
                $id .
                " has been cancelled by UID: " .
                $user .
                " Reason: " .
                $request->reason
            : "Purchase order ID:" .
                $id .
                " remaining has been cancelled by UID: " .
                $user .
                " Reason: " .
                $request->reason;

        LogHistory::create([
            "activity" => $activityDescription,
            "po_id" => $id,
            "action_by" => $user,
        ]);

        return GlobalFunction::responseFunction(
            Message::PO_CANCELLED,
            $po_cancel
        );
    }

    public function cancel_po_item(PORequest $request, $orderItemId)
    {
        $no_rr = $request->no_rr;
        $user = auth()->user()->id;

        $order = POItems::with("po_transaction")->find($orderItemId);

        if (!$order) {
            return GlobalFunction::notFound(Message::INVALID_ACTION);
        }

        $po = $order->po_transaction;

        if ($po->status === "Cancelled") {
            return GlobalFunction::invalid(Message::PO_CANCELLED_ALREADY);
        }

        $prItem = PRItems::find($order->pr_item_id);
        if ($prItem) {
            $partialReceived = $order->quantity_serve;
            $remainingQty = $order->quantity - $order->quantity_serve;

            $prItem->update([
                "partial_received" => $partialReceived,
                "remaining_qty" => $remainingQty,
                "buyer_id" => null,
                "buyer_name" => null,
                "supplier_id" => null,
                "po_at" => null,
                "purchase_order_id" => null,
            ]);
        }

        $order->delete();

        $remainingOrders = $po->order()->count();
        $statusNote =
            $remainingOrders === 0
                ? "All items now cancelled"
                : "Partial cancellation";

        $activityDescription = $no_rr
            ? "PO Item ID: {$orderItemId} under PO ID: {$po->id} was cancelled by UID: {$user}. Reason: {$request->reason}. Status: {$statusNote}"
            : "PO Item ID: {$orderItemId} (no RR) under PO ID: {$po->id} was cancelled by UID: {$user}. Reason: {$request->reason}. Status: {$statusNote}";

        LogHistory::create([
            "activity" => $activityDescription,
            "po_id" => $po->id,
            "action_by" => $user,
        ]);

        return GlobalFunction::responseFunction(
            "PO Item successfully cancelled.",
            $orderItemId
        );
    }

    public function cancel_jo_po(PORequest $request, $id)
    {
        $no_rr = $request->no_rr;
        $user = Auth()->user()->id;
        $po_cancel = JOPOTransaction::where("id", $id)
            ->with("jo_po_orders")
            ->get()
            ->first();

        if (!$po_cancel) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $direct_jo = $po_cancel->direct_po;

        if ($direct_jo) {
            $jr_transaction = JobOrderTransaction::where(
                "id",
                $po_cancel->jo_number
            )->first();

            $jr_transaction->update([
                "status" => "Return",
                "reason" => $reason,
            ]);
        }

        if ($no_rr) {
            $po_cancel_order = $po_cancel
                ->jo_po_orders()
                ->pluck("jo_item_id")
                ->toArray();

            $pr_items = JobItems::whereIn("id", $po_cancel_order)->update([
                "po_at" => null,
                "purchase_order_id" => null,
            ]);
        }

        $po_cancel->update([
            "reason" => $request->reason,
            "rejected_at" => null,
            "cancelled_at" => Carbon::now()
                ->timeZone("Asia/Manila")
                ->format("Y-m-d H:i"),
            "status" => "Cancelled",
            "approved_at" => null,
        ]);

        $po_cancel->jo_po_orders()->delete();
        $po_cancel->delete();

        $activityDescription = $no_rr
            ? "Purchase order ID:" .
                $id .
                " has been cancelled by UID: " .
                $user .
                " Reason: " .
                $request->reason
            : "Purchase order ID:" .
                $id .
                " remaining has been cancelled by UID: " .
                $user .
                " Reason: " .
                $request->reason;

        LogHistory::create([
            "activity" => $activityDescription,
            "jo_po_id" => $id,
            "action_by" => $user,
        ]);

        return GlobalFunction::responseFunction(
            Message::PO_CANCELLED,
            $po_cancel
        );
    }

    public function po_badge(Request $request)
    {
        $user = Auth()->user()->id;

        $approver_histories = JoPoHistory::where("approver_id", $user)->get();

        $po_id = BadgeHelperFunctions::poId($user);
        $layer = BadgeHelperFunctions::layer($user);

        // $jo_po_id = BadgeHelperFunctions::joPoId($approver_histories);
        // $jo_layer = BadgeHelperFunctions::joLayer($approver_histories);

        // Get all JO PO IDs and their corresponding layers for the user
        $userLayers = JoPoHistory::where("approver_id", $user)
            ->pluck("layer", "jo_po_id") // JO PO ID => Layer assigned to the user
            ->toArray();

        $job_order_count = JOPOTransaction::whereHas(
            "jo_approver_history",
            function ($query) use ($user, $userLayers) {
                $query
                    ->whereNull("approved_at")
                    ->where("approver_id", $user)
                    ->whereIn("jo_po_id", array_keys($userLayers))
                    ->whereIn("layer", array_values($userLayers));
            }
        )
            ->where(function ($query) use ($userLayers) {
                foreach ($userLayers as $joPoId => $layer) {
                    $query->orWhere(function ($subQuery) use ($joPoId, $layer) {
                        $subQuery->where("id", $joPoId)->where("layer", $layer);
                    });
                }
            })
            ->where(function ($query) {
                $query
                    ->where("status", "Pending")
                    ->orWhere("status", "For Approval");
            })
            ->whereNull("voided_at")
            ->whereNull("cancelled_at")
            ->whereNull("rejected_at")
            ->count();

        $result = [
            "Inventoriables" => BadgeHelperFunctions::getPrCount(
                $po_id,
                $layer,
                "Inventoriable"
            ),
            "Expense" => BadgeHelperFunctions::getPrCount(
                $po_id,
                $layer,
                "expense"
            ),
            "Assets" => BadgeHelperFunctions::getPrCount(
                $po_id,
                $layer,
                "assets"
            ),
            "Job Order" => $job_order_count,
            // "Job Order" => BadgeHelperFunctions::poJobOrderCount(
            //     $jo_po_id,
            //     $jo_layer
            // ),
        ];

        return GlobalFunction::responseFunction(
            Message::DISPLAY_COUNT,
            $result
        );
    }

    public function update_remarks(Request $request, $id)
    {
        $po_item = POItems::find($id);

        if (!$po_item) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $remarks = $request->remarks;

        $po_item->remarks = $remarks;

        $po_item->save();

        $po_item->fresh();

        $po_collect = new PoItemResource($po_item);

        return GlobalFunction::save(Message::REMARKS_UPDATE, $po_collect);
    }

    public function um_sync()
    {
        return $po_transation = POTransaction::get();
    }
}
