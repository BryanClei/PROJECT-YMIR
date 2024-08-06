<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Type;
use App\Models\User;
use App\Models\PRItems;
use App\Models\PrHistory;
use App\Response\Message;
use App\Models\JobHistory;
use App\Models\LogHistory;
use App\Models\SetApprover;
use Illuminate\Http\Request;
use App\Models\PRTransaction;
use App\Models\PrTransaction2;
use App\Models\ApproverSettings;
use App\Functions\GlobalFunction;
use App\Models\AssetsTransaction;
use App\Models\JobOrderTransaction;
use App\Http\Controllers\Controller;
use App\Http\Requests\PRViewRequest;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\PRTransactionResource;
use App\Http\Requests\PurchaseRequest\AssetRequest;
use App\Http\Requests\PurchaseRequest\StoreRequest;
use App\Http\Requests\PurchaseRequest\UploadRequest;

class PRTransactionController extends Controller
{
    public function store_file(Request $request)
    {
        // $path = $request->file("file")->store("public/attachment/");

        $name = $request->file("file")->getClientOriginalName();
        return $name;
    }

    public function index(PRViewRequest $request)
    {
        $status = $request->status;
        $user_id = Auth()->user()->id;

        $purchase_request = PRTransaction::with(
            "order",
            "approver_history",
            "po_transaction.order",
            "po_transaction.approver_history"
        )
            ->where("module_name", "Inventoriables")
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

    public function assets(PRViewRequest $request)
    {
        $status = $request->status;
        $type = $request->type;
        $user_id = Auth()->user()->id;

        $purchase_request = AssetsTransaction::with("order", "approver_history")
            ->where("module_name", "Assets")
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

    public function store(StoreRequest $request)
    {
        $user_id = Auth()->user()->id;

        if ($request->boolean("for_po_only")) {
            $for_po_id = $user_id;
            $date_today = Carbon::now()
                ->timeZone("Asia/Manila")
                ->format("Y-m-d H:i");
        } else {
            $for_po_id = null;
            $date_today = null;
        }

        $orders = $request->order;

        $current_year = date("Y");
        $latest_pr = PRTransaction::where(
            "pr_year_number_id",
            "like",
            $current_year . "-P-%"
        )
            ->orderByRaw(
                "CAST(SUBSTRING_INDEX(pr_year_number_id, '-', -1) AS UNSIGNED) DESC"
            )
            ->first();

        if ($latest_pr) {
            $latest_number = explode("-", $latest_pr->pr_year_number_id)[2];
            $new_number = (int) $latest_number + 1;
        } else {
            $new_number = 1;
        }

        $latest_pr_number = PRTransaction::max("pr_number") ?? 0;
        $pr_number = $latest_pr_number + 1;

        $pr_year_number_id =
            $current_year . "-P-" . str_pad($new_number, 3, "0", STR_PAD_LEFT);

        $purchase_request = new PRTransaction([
            "pr_year_number_id" => $pr_year_number_id,
            "pr_number" => $pr_number,
            "pr_description" => $request["pr_description"],
            "date_needed" => $request["date_needed"],
            "user_id" => $user_id,
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
            "module_name" => "Inventoriables",
            "status" => "Pending",
            "asset" => $request->asset,
            "sgp" => $request->sgp,
            "f1" => $request->f1,
            "f2" => $request->f2,
            "for_po_only" => $date_today,
            "for_po_only_id" => $for_po_id,
            "layer" => "1",
            "description" => $request->description,
        ]);
        $purchase_request->save();

        foreach ($orders as $index => $values) {
            PRItems::create([
                "transaction_id" => $purchase_request->id,
                "item_id" => $request["order"][$index]["item_id"],
                "item_code" => $request["order"][$index]["item_code"],
                "item_name" => $request["order"][$index]["item_name"],
                "uom_id" => $request["order"][$index]["uom_id"],
                "quantity" => $request["order"][$index]["quantity"],
                "remarks" => $request["order"][$index]["remarks"],
                "attachment" => $request["order"][$index]["attachment"],
                "assets" => $request["order"][$index]["assets"],
                "warehouse_id" => $request["order"][$index]["warehouse_id"],
            ]);
        }
        $approver_settings = ApproverSettings::where(
            "company_id",
            $purchase_request->company_id
        )
            ->where("business_unit_id", $purchase_request->business_unit_id)
            ->where("department_id", $purchase_request->department_id)
            ->where("department_unit_id", $purchase_request->department_unit_id)
            ->where("sub_unit_id", $purchase_request->sub_unit_id)
            ->where("location_id", $purchase_request->location_id)
            ->whereHas("set_approver")
            ->get()
            ->first();

        $approvers = SetApprover::where(
            "approver_settings_id",
            $approver_settings->id
        )->get();

        if ($approvers->isEmpty()) {
            return GlobalFunction::save(Message::NO_APPROVERS);
        }

        foreach ($approvers as $index) {
            PrHistory::create([
                "pr_id" => $purchase_request->id,
                "approver_id" => $index["approver_id"],
                "approver_name" => $index["approver_name"],
                "layer" => $index["layer"],
            ]);
        }

        $activityDescription =
            "Purchase request ID: " .
            $purchase_request->id .
            " has been created by UID: " .
            $user_id;

        LogHistory::create([
            "activity" => $activityDescription,
            "pr_id" => $purchase_request->id,
            "action_by" => $user_id,
        ]);

        $pr_collect = new PRTransactionResource($purchase_request);

        return GlobalFunction::save(
            Message::PURCHASE_REQUEST_SAVE,
            $pr_collect
        );
    }

    public function asset_sync(AssetRequest $request)
    {
        $assets = $request->all();
        $user_id = Auth()->user()->id;

        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $current_year = date("Y");
        $latest_pr = PRTransaction::where(
            "pr_year_number_id",
            "like",
            $current_year . "-V-%"
        )
            ->orderBy("pr_year_number_id", "desc")
            ->first();

        if ($latest_pr) {
            $latest_number = intval(
                explode("-V-", $latest_pr->pr_year_number_id)[1]
            );
            $new_number = $latest_number + 1;
        } else {
            $new_number = 1;
        }

        $latest_pr_number = PRTransaction::max("pr_number") ?? 0;
        $pr_number = $latest_pr_number + 1;

        foreach ($assets as $sync) {
            $pr_year_number_id =
                $current_year .
                "-V-" .
                str_pad($new_number, 3, "0", STR_PAD_LEFT);

            $type_id = Type::where("name", "Assets")
                ->get()
                ->first();

            $purchase_request = new PRTransaction([
                "pr_year_number_id" => $pr_year_number_id,
                "pr_number" => $pr_number,
                "transaction_no" => $sync["transaction_number"],
                "pr_description" => $sync["pr_description"],
                "date_needed" => $sync["date_needed"],
                "user_id" => $sync["vrid"],
                "type_id" => $type_id->id,
                "type_name" => $type_id->name,
                "business_unit_id" => $sync["business_unit_id"],
                "business_unit_name" => $sync["business_unit_name"],
                "company_id" => $sync["company_id"],
                "company_name" => $sync["company_name"],
                "department_id" => $sync["department_id"],
                "department_name" => $sync["department_name"],
                "department_unit_id" => $sync["department_unit_id"],
                "department_unit_name" => $sync["department_unit_name"],
                "location_id" => $sync["location_id"],
                "location_name" => $sync["location_name"],
                "sub_unit_id" => $sync["sub_unit_id"],
                "sub_unit_name" => $sync["sub_unit_name"],
                "account_title_id" => $sync["account_title_id"],
                "account_title_name" => $sync["account_title_name"],
                "module_name" => $type_id->name,
                "transaction_number" => $sync["transaction_number"],
                "status" => "Approved",
                // "asset" => $sync["asset"],
                "sgp" => $sync["sgp"],
                "f1" => $sync["f1"],
                "f2" => $sync["f2"],
                "layer" => "1",
                "for_po_only" => $date_today,
                "for_po_only_id" => $sync["vrid"],
                "vrid" => $sync["vrid"],
                "approved_at" => $date_today,
            ]);
            $purchase_request->save();

            $activityDescription =
                "Purchase request ID: " .
                $purchase_request->id .
                " has been created by UID: " .
                $user_id;

            LogHistory::create([
                "activity" => $activityDescription,
                "pr_id" => $purchase_request->id,
                "action_by" => $sync["vrid"],
            ]);

            $orders = $sync["order"];

            foreach ($orders as $index => $values) {
                PRItems::create([
                    "transaction_id" => $purchase_request->id,
                    "item_code" => $values["item_code"],
                    "item_name" => $values["item_name"],
                    "uom_id" => $values["uom_id"],
                    "quantity" => $values["quantity"],
                    "remarks" => $values["remarks"],
                ]);
            }
            $new_number++;
            $pr_number++;

            // $approver_settings = ApproverSettings::where(
            //     "company_id",
            //     $purchase_request->company_id
            // )
            //     ->where("business_unit_id", $purchase_request->business_unit_id)
            //     ->where("department_id", $purchase_request->department_id)
            //     ->where(
            //         "department_unit_id",
            //         $purchase_request->department_unit_id
            //     )
            //     ->where("sub_unit_id", $purchase_request->sub_unit_id)
            //     ->where("location_id", $purchase_request->location_id)
            //     ->whereHas("set_approver")
            //     ->get()
            //     ->first();

            // $approvers = SetApprover::where(
            //     "approver_settings_id",
            //     $approver_settings->id
            // )->get();

            // if ($approvers->isEmpty()) {
            //     return GlobalFunction::save(Message::NO_APPROVERS);
            // }

            // foreach ($approvers as $index) {
            //     PrHistory::create([
            //         "pr_id" => $purchase_request->id,
            //         "approver_id" => $index["approver_id"],
            //         "approver_name" => $index["approver_name"],
            //         "approved_at" => $date_today,
            //         "layer" => $index["layer"],
            //     ]);
            // }

            $activityDescription =
                "Purchase request ID: " .
                $purchase_request->id .
                " has been created by UID: " .
                $user_id;

            LogHistory::create([
                "activity" => $activityDescription,
                "pr_id" => $purchase_request->id,
                "action_by" => $user_id,
            ]);
        }

        return GlobalFunction::save(Message::PURCHASE_REQUEST_SAVE, $assets);
    }

    public function update(StoreRequest $request, $id)
    {
        $purchase_request = PRTransaction::find($id);
        $user_id = Auth()->user()->id;
        $not_found = PRTransaction::where("id", $id)->exists();

        if (!$not_found) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        if ($request->boolean("for_po_only")) {
            $for_po_id = $user_id;
            $date_today = Carbon::now()
                ->timeZone("Asia/Manila")
                ->format("Y-m-d H:i");
        } else {
            $for_po_id = null;
            $date_today = null;
        }

        $orders = $request->order;

        $purchase_request->update([
            "pr_number" => $purchase_request->id,
            "pr_description" => $request["pr_description"],
            "date_needed" => $request["date_needed"],
            "user_id" => $user_id,
            "type_id" => $request["type_id"],
            "type_name" => $request["type_name"],
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
            "module_name" => "Inventoriables",

            "description" => $request->description,
            "asset" => $request->asset,
            "sgp" => $request->sgp,
            "f1" => $request->f1,
            "f2" => $request->f2,
            "for_po_only" => $date_today,
            "for_po_only_id" => $for_po_id,
        ]);

        $newOrders = collect($orders)
            ->pluck("id")
            ->toArray();
        $currentOrders = PRItems::where("transaction_id", $id)
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
                    "transaction_id" => $purchase_request->id,
                    "item_id" => $values["item_id"],
                    "item_code" => $values["item_code"],
                    "item_name" => $values["item_name"],
                    "uom_id" => $values["uom_id"],
                    "quantity" => $values["quantity"],
                    "remarks" => $values["remarks"],
                    "attachment" => $values["attachment"],
                    "assets" => $values["assets"],
                ]
            );
        }

        $activityDescription =
            "Purchase request ID: " .
            $id .
            " has been updated by UID: " .
            $user_id;

        LogHistory::create([
            "activity" => $activityDescription,
            "pr_id" => $id,
            "action_by" => $user_id,
        ]);

        $pr_collect = new PRTransactionResource($purchase_request);

        return GlobalFunction::save(
            Message::PURCHASE_REQUEST_UPDATE,
            $pr_collect
        );
    }

    public function resubmit(Request $request, $id)
    {
        $purchase_request = PRTransaction::find($id);

        $user_id = Auth()->user()->id;
        $not_found = PRTransaction::where("id", $id)->exists();

        if (!$not_found) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $pr_history = PrHistory::where("pr_id", $id)->get();

        if ($pr_history->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        foreach ($pr_history as $pr) {
            $pr->update([
                "approved_at" => null,
                "rejected_at" => null,
            ]);
        }

        $orders = $request->order;

        $purchase_request->update([
            "pr_number" => $purchase_request->id,
            "pr_description" => $request["pr_description"],
            "date_needed" => $request["date_needed"],
            "user_id" => $user_id,
            "type_id" => $request["type_id"],
            "type_name" => $request["type_name"],
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
            "module_name" => "Inventoriables",
            "status" => "Pending",
            "description" => $request->description,
            "rejected_at" => null,
            "asset" => $request->asset,
            "sgp" => $request->sgp,
            "f1" => $request->f1,
            "f2" => $request->f2,
            "layer" => "1",
        ]);

        $newOrders = collect($orders)
            ->pluck("id")
            ->toArray();
        $currentOrders = PRItems::where("transaction_id", $id)
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
                    "transaction_id" => $purchase_request->id,
                    "item_id" => $values["item_id"],
                    "item_code" => $values["item_code"],
                    "item_name" => $values["item_name"],
                    "uom_id" => $values["uom_id"],
                    "quantity" => $values["quantity"],
                    "remarks" => $values["remarks"],
                    "assets" => $values["assets"],
                ]
            );
        }

        $activityDescription =
            "Purchase request ID: " .
            $id .
            " has been resubmitted by UID: " .
            $user_id;

        LogHistory::create([
            "activity" => $activityDescription,
            "pr_id" => $id,
            "action_by" => $user_id,
        ]);

        $pr_collect = new PRTransactionResource($purchase_request);

        return GlobalFunction::responseFunction(
            Message::RESUBMITTED,
            $pr_collect
        );
    }

    public function destroy($id)
    {
        $purchase_request = PRTransaction::where("id", $id)
            ->withTrashed()
            ->get();

        if ($purchase_request->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $purchase_request = PRTransaction::withTrashed()->find($id);
        $is_active = PRTransaction::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $purchase_request->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $purchase_request->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $purchase_request);
    }

    public function store_multiple(UploadRequest $request, $id)
    {
        $files = $request->file("files");
        $uploadedFiles = [];

        if (!$files) {
            $message = Message::NO_FILE_UPLOAD;
            return GlobalFunction::uploadfailed($message, $files);
        }

        $successCount = 0;
        foreach ($files as $file) {
            if (!$file->isValid()) {
                continue;
            }

            $name_with_extension = $file->getClientOriginalName();
            $name = pathinfo($name_with_extension, PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();

            $filename =
                $name . "_" . $id . "_" . ++$successCount . "." . $extension;

            $stored = Storage::putFileAs("public/attachment", $file, $filename);

            if ($stored) {
                $uploadedFiles[] = $filename;
                continue;
            } else {
                $message = "Failed to store file: $filename";
                return GlobalFunction::uploadfailed($message, $files); // This should return the JsonResponse directly
            }
        }

        $message = Message::UPLOAD_SUCCESSFUL;
        $response = GlobalFunction::stored($message);
        $responseData = $response->getData(true);
        $responseData["uploaded_files"] = $uploadedFiles;
        return response()->json($responseData, $response->getStatusCode());
    }

    public function download($filename)
    {
        if (!Storage::exists("public/attachment/" . $filename)) {
            $message = Message::FILE_NOT_FOUND;
            return GlobalFunction::uploadfailed(
                $message,
                $filename
            )->setStatusCode(Message::DATA_NOT_FOUND);
        }

        $path = storage_path("app/public/attachment/" . $filename);
        $file_path = "app/public/attachment/" . $filename;

        return response()
            ->download($path, $filename)
            ->setStatusCode(200);
    }

    public function buyer(Request $request, $id)
    {
        $user_id = Auth()->user()->id;
        $purchase_request = PRTransaction::with("order")->find($id);

        $payload = $request->all();
        $is_update = $payload["updated"] ?? false;
        $payload_items = $payload["items"] ?? $payload;

        $item_ids = array_column($payload_items, "id");

        $pr_items = PRItems::whereIn("id", $item_ids)
            ->where("transaction_id", $id)
            ->get();

        if ($pr_items->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $item_details = [];
        foreach ($pr_items as $item) {
            $payloadItem = collect($payload_items)->firstWhere("id", $item->id);
            $old_buyer = $item->buyer_name;
            $old_buyer_id = $item->buyer_id;

            $item->update([
                "buyer_id" => $payloadItem["buyer_id"],
                "buyer_name" => $payloadItem["buyer_name"],
            ]);

            $item_details[] = $is_update
                ? "Item ID {$item->id}: Buyer reassigned from {$old_buyer} (ID: {$old_buyer_id}) to {$payloadItem["buyer_name"]} (ID: {$payloadItem["buyer_id"]})"
                : "Item ID {$item->id}: Buyer assigned to {$payloadItem["buyer_name"]} (ID: {$payloadItem["buyer_id"]})";
        }

        $item_details_string = implode(", ", $item_details);

        $activityDescription = $is_update
            ? "Purchase request ID: $id - has been re-tagged to buyer for " .
                count($item_details) .
                " item(s). Details: " .
                $item_details_string
            : "Purchase request ID: $id -  has been tagged to buyer for " .
                count($item_details) .
                " item(s). Details: " .
                $item_details_string;

        //        if ($is_update) {
        //            $activityDescription =
        //                "Purchase request ID: $id - has been re-tagged to buyer for " .
        //                count($item_details) .
        //                " item(s). Details: " .
        //                $item_details_string;
        //        } else {
        //            $activityDescription =
        //                "Purchase request ID: $id -  has been tagged to buyer for " .
        //                count($item_details) .
        //                " item(s). Details: " .
        //                $item_details_string;
        //        }

        LogHistory::create([
            "activity" => $activityDescription,
            "pr_id" => $id,
            "action_by" => $user_id,
        ]);

        return GlobalFunction::responseFunction(
            $is_update ? Message::BUYER_UPDATED : Message::BUYER_TAGGED,
            $pr_items
        );
    }

    public function pr_badge(Request $request)
    {
        $user = Auth()->user()->id;

        $user_id = User::where("id", $user)
            ->get()
            ->first();

        $pr_id = PrHistory::where("approver_id", $user)
            ->get()
            ->pluck("pr_id");
        $layer = PrHistory::where("approver_id", $user)
            ->get()
            ->pluck("layer");

        $pr_inventoriables_count = PRTransaction::where(
            "type_name",
            "Inventoriable"
        )
            ->whereIn("id", $pr_id)
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
        $pr_expense_count = PRTransaction::where("type_name", "expense")
            ->whereIn("id", $pr_id)
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
        $pr_assets_count = PRTransaction::where("type_name", "asset")
            ->whereIn("id", $pr_id)
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

        $user_id = User::where("id", $user)
            ->get()
            ->first();

        $jo_id = JobHistory::where("approver_id", $user)
            ->get()
            ->pluck("jo_id");
        $layer = JobHistory::where("approver_id", $user)
            ->get()
            ->pluck("layer");

        $pr_job_order_count = JobOrderTransaction::where(
            "type_name",
            "Job Order"
        )
            ->whereIn("id", $jo_id)
            ->whereIn("layer", $layer)
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
            "Inventoriables" => $pr_inventoriables_count,
            "Expense" => $pr_expense_count,
            "Assets" => $pr_assets_count,
            "Job Order" => $pr_job_order_count,
        ];

        return GlobalFunction::responseFunction(
            Message::DISPLAY_COUNT,
            $result
        );
    }
}
