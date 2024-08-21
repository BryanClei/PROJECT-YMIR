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
use App\Http\Requests\PO\PORequest;
use App\Models\JobOrderTransaction;
use App\Http\Controllers\Controller;
use App\Http\Requests\PRViewRequest;
use App\Http\Resources\JoPoResource;
use App\Http\Resources\PoItemResource;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\JoPo\StoreRequest;

class PoController extends Controller
{
    public function index(PRViewRequest $request)
    {
        $status = $request->status;
        $user_id = Auth()->user()->id;

        $purchase_order = POTransaction::orderByDesc("updated_at")
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

    public function store(Request $request)
    {
        $user_id = Auth()->user()->id;

        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $orders = $request->order;

        if ($request->module_name === "Asset") {
            $if_exist = PRTransaction::where(
                "pr_number",
                $request->pr_number
            )->get();
        } else {
            $if_exist = PRTransaction::where("id", $request->pr_number)->get();
        }

        if ($if_exist->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $po_number = POTransaction::max("id") ?? 0;

        $increment = $po_number ? $po_number + 1 : 1;

        $purchase_order = new POTransaction([
            "po_number" => $increment,
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
            "layer" => "1",
            "description" => $request->description,
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
                "item_id" => $request["order"][$index]["item_id"],
                "item_code" => $request["order"][$index]["item_code"],
                "item_name" => $request["order"][$index]["item_name"],
                "uom_id" => $request["order"][$index]["uom_id"],
                "supplier_id" => $request["order"][$index]["supplier_id"],
                "price" => $request["order"][$index]["price"],
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

        $po_settings = POSettings::where(
            "company_id",
            $purchase_order->company_id
        )
            ->get()
            ->first();

        $purchase_items = POItems::where("po_id", $purchase_order->id)
            ->get()
            ->pluck("total_price")
            ->toArray();

        $sum = array_sum($purchase_items);

        $approvers = PoApprovers::where("price_range", "<=", $sum)
            ->where("po_settings_id", $po_settings->company_id)
            ->get();

        foreach ($approvers as $index) {
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

        $jo_number = JOPOTransaction::latest()
            ->get()
            ->first();
        $increment = $jo_number ? $jo_number->id + 1 : 1;

        $job_order = new JOPOTransaction([
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
            "status" => "Pending",
            "asset" => $request->asset,
            "sgp" => $request->sgp,
            "f1" => $request->f1,
            "f2" => $request->f2,
            "layer" => "1",
            "description" => $request->description,
        ]);
        $job_order->save();

        foreach ($orders as $index => $values) {
            JoPoOrders::create([
                "jo_po_id" => $job_order->id,
                "jo_transaction_id" => $job_order->jo_number,
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
            ]);
        }

        foreach ($orders as $index => $values) {
            $item_id = $request["order"][$index]["id"];
            $items = JobItems::where("id", $item_id)->update([
                "po_at" => $date_today,
                "purchase_order_id" => $job_order->id,
            ]);
        }

        $activityDescription =
            "Job order purchase order ID: " .
            $job_order->id .
            " has been created by UID: " .
            $user_id;

        LogHistory::create([
            "activity" => $activityDescription,
            "jo_po_id" => $job_order->id,
            "action_by" => $user_id,
        ]);

        $po_settings = POSettings::where("company_id", $job_order->company_id)
            ->get()
            ->first();

        $jo_items = JoPoOrders::where("jo_po_id", $job_order->id)
            ->get()
            ->pluck("total_price")
            ->toArray();

        $sum = array_sum($jo_items);

        $approvers = PoApprovers::where("price_range", "<=", $sum)
            ->where("po_settings_id", $po_settings->company_id)
            ->get();

        foreach ($approvers as $index) {
            JoPoHistory::create([
                "jo_po_id" => $job_order->id,
                "approver_id" => $index["approver_id"],
                "approver_name" => $index["approver_name"],
                "layer" => $index["layer"],
            ]);
        }

        $jo_collect = new JoPoResource($job_order);

        return GlobalFunction::save(Message::JOB_ORDER_SAVE, $jo_collect);
    }

    public function update(Request $request, $id)
    {
        $user_id = Auth()->user()->id;
        $purchase_order = POTransaction::find($id);

        $not_found = POTransaction::where("id", $id)->exists();

        if (!$not_found) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

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
            "layer" => "1",
            "description" => $request->description,
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
            POItems::withTrashed()->updateOrCreate(
                [
                    "id" => $values["id"] ?? null,
                ],
                [
                    "po_id" => $purchase_order->id,
                    "pr_id" => $purchase_order->pr_number,
                    "item_id" => $values["item_id"],
                    "item_code" => $values["item_code"],
                    "item_name" => $values["item_name"],
                    "uom_id" => $values["uom_id"],
                    "supplier_id" => $values["supplier_id"],
                    "price" => $values["price"],
                    "quantity" => $values["quantity"],
                    "quantity_serve" => $values["quantity_serve"],
                    "total_price" => $values["total_price"],
                    "attachment" => $values["attachment"],
                    "buyer_id" => $values["buyer_id"],
                    "buyer_name" => $values["buyer_name"],
                    "remarks" => $values["remarks"],
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
            "status" => "Pending",
            "rejected_at" => null,
            "reason" => null,
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
                    "item_id" => $values["item_id"],
                    "item_code" => $values["item_code"],
                    "item_name" => $values["item_name"],
                    "uom_id" => $values["uom_id"],
                    "supplier_id" => $values["supplier_id"],
                    "price" => $values["price"],
                    "quantity" => $values["quantity"],
                    "quantity_serve" => $values["quantity_serve"],
                    "total_price" => $newTotalPrice,
                    "attachment" => $values["attachment"],
                    "buyer_id" => $values["buyer_id"],
                    "buyer_name" => $values["buyer_name"],
                    "remarks" => $values["remarks"],
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
                ->where("po_settings_id", $po_settings->company_id)
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
        }

        foreach ($po_approvers as $po_approver) {
            $po_approver->update([
                "approved_at" => null,
                "rejected_at" => null,
            ]);
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

        $user_id = Auth()->user()->id;
        $not_found = JOPOTransaction::where("id", $id)->exists();

        if (!$not_found) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $po_history = JoPoHistory::where("jo_po_id", $id)->get();

        if ($po_history->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        foreach ($po_history as $pr) {
            $pr->update([
                "approved_at" => null,
                "rejected_at" => null,
            ]);
        }

        $orders = $request->order;

        $job_order->update([
            "status" => "Pending",
            "rejected_at" => null,
            "reason" => null,
            "layer" => "1",
        ]);

        $activityDescription =
            "Job order purchase order ID: " .
            $id .
            " has been resubmitted by UID: " .
            $user_id;

        $log = LogHistory::create([
            "activity" => $activityDescription,
            "jo_po_id" => $id,
            "action_by" => $user_id,
        ]);

        $po_settings = POSettings::where("company_id", $job_order->company_id)
            ->get()
            ->first();

        $jo_items = JoPoOrders::where("jo_po_id", $job_order->id)
            ->get()
            ->pluck("total_price")
            ->toArray();

        $sum = array_sum($jo_items);

        $approvers = PoApprovers::where("price_range", "<=", $sum)
            ->where("po_settings_id", $po_settings->company_id)
            ->get();

        foreach ($approvers as $index) {
            $exists = JoPoHistory::where([
                ["jo_po_id", $job_order->id],
                ["approver_id", $index["approver_id"]],
            ])->exists();

            if (!$exists) {
                JoPoHistory::create([
                    "jo_po_id" => $job_order->id,
                    "approver_id" => $index["approver_id"],
                    "approver_name" => $index["approver_name"],
                    "layer" => $index["layer"],
                ]);
            }
        }

        foreach ($orders as $index => $values) {
            $order_id = $values["id"];
            JoPoOrders::where("id", $order_id)
                ->withTrashed()
                ->update([
                    "unit_price" => $values["price"],
                    "total_price" => $values["total_price"],
                ]);
        }

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

        if ($no_rr) {
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

            $po_cancel->order()->delete();
        }

        $po_cancel->update([
            "reason" => $request->reason,
            "rejected_at" => null,
            "cancelled_at" => Carbon::now()
                ->timeZone("Asia/Manila")
                ->format("Y-m-d H:i"),
            "status" => "Cancelled",
            "deleted_at" => Carbon::now()
                ->timeZone("Asia/Manila")
                ->format("Y-m-d H:i"),
        ]);

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

    public function po_badge(Request $request)
    {
        $user = Auth()->user()->id;

        $po_id = PoHistory::where("approver_id", $user)
            ->get()
            ->pluck("po_id");
        $layer = PoHistory::where("approver_id", $user)
            ->get()
            ->pluck("layer");

        $pr_inventoriables_count = POTransaction::where(
            "type_name",
            "Inventoriables"
        )
            ->whereIn("id", $po_id)
            ->whereIn("layer", $layer)
            ->where(function ($query) {
                $query
                    ->where("status", "Pending")
                    ->orWhere("status", "For Approval");
            })
            ->whereNull("voided_at")
            ->whereNull("cancelled_at")
            ->whereNull("rejected_at")
            ->whereHas("approver_history", function ($query) {
                $query->whereNull("approved_at");
            })
            ->count();
        $pr_expense_count = POTransaction::where("type_name", "expenses")
            ->whereIn("id", $po_id)
            ->whereIn("layer", $layer)
            ->where(function ($query) {
                $query
                    ->where("status", "Pending")
                    ->orWhere("status", "For Approval");
            })
            ->whereNull("voided_at")
            ->whereNull("cancelled_at")
            ->whereNull("rejected_at")
            ->whereHas("approver_history", function ($query) {
                $query->whereNull("approved_at");
            })
            ->count();
        $pr_assets_count = POTransaction::where("type_name", "assets")
            ->whereIn("id", $po_id)
            ->whereIn("layer", $layer)
            ->where(function ($query) {
                $query
                    ->where("status", "Pending")
                    ->orWhere("status", "For Approval");
            })
            ->whereNull("voided_at")
            ->whereNull("cancelled_at")
            ->whereNull("rejected_at")
            ->whereHas("approver_history", function ($query) {
                $query->whereNull("approved_at");
            })
            ->count();

        $po_id = JoPoHistory::where("approver_id", $user)
            ->get()
            ->pluck("jo_po_id");
        $layer = JoPoHistory::where("approver_id", $user)
            ->get()
            ->pluck("layer");

        $po_job_order_count = JOPOTransaction::where("module_name", "Job Order")
            ->whereIn("id", $po_id)
            ->whereIn("layer", $layer)
            ->where(function ($query) {
                $query
                    ->where("status", "Pending")
                    ->orWhere("status", "For Approval");
            })
            ->whereNull("voided_at")
            ->whereNull("cancelled_at")
            ->whereNull("rejected_at")
            ->whereHas("jo_approver_history", function ($query) {
                $query->whereNull("approved_at");
            })
            ->count();

        $result = [
            "Inventoriables" => $pr_inventoriables_count,
            "Expense" => $pr_expense_count,
            "Assets" => $pr_assets_count,
            "Job Order" => $po_job_order_count,
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

        // if (
        //     !isset($remarks[0]["description"]) ||
        //     !is_array($remarks[0]["description"])
        // ) {
        //     $remarks[0]["description"] = [];
        // }
        // if (
        //     !isset($remarks[0]["quantity"]) ||
        //     !is_array($remarks[0]["quantity"])
        // ) {
        //     $remarks[0]["quantity"] = [];
        // }

        // if ($request->has("indices")) {
        //     $indices = $request["indices"];
        //     $remarks_descriptions = $request["remarks_descriptions"];
        //     $remarks_quantities = $request["remarks_quantities"];

        //     foreach ($indices as $key => $index) {
        //         $remarks[0]["description"][$index] =
        //             $remarks_descriptions[$key];
        //         $remarks[0]["quantity"][$index] = $remarks_quantities[$key];
        //     }
        // }

        // if ($request->has(["new_description", "new_quantity"])) {
        //     foreach ($request["new_description"] as $key => $value) {
        //         $remarks[0]["description"][] = $value;
        //         $remarks[0]["quantity"][] = $request["new_quantity"][$key];
        //     }
        // }

        // if ($request->has("delete_indices")) {
        //     $delete_indices = $request["delete_indices"];
        //     foreach ($delete_indices as $index) {
        //         unset($remarks[0]["description"][$index]);
        //         unset($remarks[0]["quantity"][$index]);
        //     }
        //     $remarks[0]["description"] = array_values(
        //         $remarks[0]["description"]
        //     );
        //     $remarks[0]["quantity"] = array_values($remarks[0]["quantity"]);
        // }

        // $po_item->remarks = json_encode($remarks);

        $po_item->remarks = $remarks;

        $po_item->save();

        $po_item->fresh();

        $po_collect = new PoItemResource($po_item);

        return GlobalFunction::save(Message::REMARKS_UPDATE, $po_collect);
    }
}
