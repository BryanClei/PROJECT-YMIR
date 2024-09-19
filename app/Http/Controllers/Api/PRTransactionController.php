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
            ->where("module_name", "Asset")
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

        $for_po_id = $request->boolean("for_po_only") ? $user_id : null;
        $date_today = $request->boolean("for_po_only")
            ? Carbon::now()
                ->timeZone("Asia/Manila")
                ->format("Y-m-d H:i")
            : null;

        $orders = $request->order;

        $current_year = date("Y");
        $latest_pr = PRTransaction::withTrashed()
            ->where("pr_year_number_id", "like", $current_year . "-PR-%")
            ->orderByRaw(
                "CAST(SUBSTRING_INDEX(pr_year_number_id, '-', -1) AS UNSIGNED) DESC"
            )
            ->first();

        $new_number = $latest_pr
            ? (int) explode("-", $latest_pr->pr_year_number_id)[2] + 1
            : 1;

        $latest_pr_number = PRTransaction::withTrashed()->max("id") ?? 0;
        $pr_number = $latest_pr_number + 1;

        $pr_year_number_id =
            $current_year . "-PR-" . str_pad($new_number, 3, "0", STR_PAD_LEFT);

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
            $attachments = $request["order"][$index]["attachment"];
            $filenames = [];
            if (!empty($attachments)) {
                foreach ($attachments as $fileIndex => $file) {
                    $originalFilename = basename($file);
                    $info = pathinfo($originalFilename);
                    $filenameOnly = $info["filename"];
                    $extension = $info["extension"];
                    $filename = "{$filenameOnly}_pr_id_{$purchase_request->id}_item_{$index}_file_{$fileIndex}.{$extension}";
                    $filenames[] = $filename;
                }
            }

            PRItems::create([
                "transaction_id" => $purchase_request->id,
                "item_id" => $request["order"][$index]["item_id"],
                "item_code" => $request["order"][$index]["item_code"],
                "item_name" => $request["order"][$index]["item_name"],
                "uom_id" => $request["order"][$index]["uom_id"],
                "quantity" => $request["order"][$index]["quantity"],
                "remarks" => $request["order"][$index]["remarks"],
                "attachment" => json_encode($filenames),
                "assets" => $request["order"][$index]["assets"],
                "warehouse_id" => $request["order"][$index]["warehouse_id"],
                "category_id" => $request["order"][$index]["category_id"],
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

    public function return_resubmit(StoreRequest $request, $id)
    {
        $user_id = Auth()->user()->id;

        $purchase_request = PRTransaction::where("id", $id)
            ->where("status", "Return")
            ->get()
            ->first();

        if (!$purchase_request) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $orders = $request->order;

        $purchase_request->update([
            "pr_description" => $request["pr_description"],
            "date_needed" => $request["date_needed"],
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
            "approved_at" => null,
            "layer" => "1",
            "description" => $request->description,
        ]);
        $purchase_request->save();

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
                    "warehouse_id" => $values["warehouse_id"],
                    "category_id" => $values["category_id"],
                ]
            );
        }

        $activityDescription =
            "Returned Purchase request ID: " .
            $purchase_request->id .
            " has been resubmitted by UID: " .
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
            $current_year . "-FA-%"
        )
            ->orderBy("pr_year_number_id", "desc")
            ->first();

        if ($latest_pr) {
            $latest_number = intval(
                explode("-FA-", $latest_pr->pr_year_number_id)[1]
            );
            $new_number = $latest_number + 1;
        } else {
            $new_number = 1;
        }

        foreach ($assets as $sync) {
            $pr_year_number_id =
                $current_year .
                "-FA-" .
                str_pad($new_number, 3, "0", STR_PAD_LEFT);

            $type_id = Type::where("name", "Assets")
                ->get()
                ->first();

            $purchase_request = new PRTransaction([
                "pr_year_number_id" => $pr_year_number_id,
                "pr_number" => $sync["pr_number"],
                "transaction_no" => $sync["transaction_number"],
                "pr_description" => $sync["pr_description"],
                "date_needed" => $sync["date_needed"],
                "user_id" => $sync["vrid"],
                "type_id" => $type_id->id,
                "type_name" => $sync["module_name"],
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
                "module_name" => $sync["module_name"],
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
                    "warehouse_id" => $values["warehouse_id"],
                    "category_id" => $values["category_id"],
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
                    "warehouse_id" => $values["warehouse_id"],
                    "category_id" => $values["category_id"],
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
        $items = $request->input("items");
        $files = $request->file("files");

        $type = $request->input("type");

        $typeSelectors = [
            "pr" => "pr",
            "po" => "po",
            "jo" => "jo",
            "rr" => "rr",
        ];

        if (!isset($typeSelectors[$type])) {
            throw new \Exception("Invalid type");
        }

        $selector = $typeSelectors[$type];

        $uploadedFiles = [];

        foreach (range(0, $items - 1) as $itemIndex) {
            if (isset($files[$itemIndex])) {
                foreach ($files[$itemIndex] as $fileIndex => $file) {
                    if (!$file->isValid()) {
                        continue;
                    }
                    $originalFilename = pathinfo(
                        $file->getClientOriginalName(),
                        PATHINFO_FILENAME
                    );
                    $filename =
                        "{$originalFilename}_{$selector}_id_{$id}_item_{$itemIndex}_file_{$fileIndex}" .
                        "." .
                        $file->getClientOriginalExtension();
                    $stored = Storage::putFileAs(
                        "public/attachment",
                        $file,
                        $filename
                    );

                    if ($stored) {
                        $uploadedFiles[] = [
                            "filename" => $filename,
                            "filepath" => "public/attachment/{$filename}",
                            "url" => url(
                                "storage/public/attachment/{$filename}"
                            ),
                        ];
                    } else {
                        $message = "Failed to store file: {$filename}";
                        return GlobalFunction::uploadfailed($message, $files);
                    }
                }
            }
        }

        $message = Message::UPLOAD_SUCCESSFUL;
        return GlobalFunction::uploadSuccessful($message, $uploadedFiles);
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

        if ($purchase_request->cancelled_at) {
            return GlobalFunction::invalid(Message::CANCELLED_ALREADY);
        }

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

        $pr_inventoriables_count = PRTransaction::where(
            "type_name",
            "Inventoriable"
        )
            ->where("user_id", $user)
            ->where("status", "Pending")
            ->orWhere("status", "For Approval")
            ->count();
        $rejected_pr_inventoriables_count = PRTransaction::where(
            "type_name",
            "Inventoriable"
        )
            ->where("user_id", $user)
            ->where("status", "Reject")
            ->whereNotNull("rejected_at")
            ->count();

        $pr_expense_count = PRTransaction::where("type_name", "expense")
            ->where("user_id", $user)
            ->where("status", "Pending")
            ->orWhere("status", "For Approval")
            ->count();
        $rejected_pr_expense_count = PRTransaction::where(
            "type_name",
            "expense"
        )
            ->where("user_id", $user)
            ->where("status", "Reject")
            ->whereNotNull("rejected_at")
            ->count();

        $rejected_pr_assets_count = PRTransaction::where("type_name", "asset")
            ->where("status", "Reject")
            ->whereNotNull("rejected_at")
            ->count();

        $pr_job_order_count = JobOrderTransaction::where(
            "type_name",
            "Job Order"
        )
            ->where("user_id", $user)
            ->where("status", "Pending")
            ->orWhere("status", "For Approval")
            ->count();
        $rejected_pr_job_order_count = JobOrderTransaction::where(
            "type_name",
            "Job Order"
        )
            ->where("user_id", $user)
            ->where("status", "Reject")
            ->whereNotNull("rejected_at")
            ->count();

        $return_invent_count = PRTransaction::where(
            "type_name",
            "Inventoriable"
        )
            ->where("user_id", $user)
            ->where("status", "Return")
            ->count();
        $return_expense_count = PRTransaction::where("type_name", "Expense")
            ->where("user_id", $user)
            ->where("status", "Return")
            ->count();

        $result = [
            "Inventoriables" => $pr_inventoriables_count,
            "rejected_inventoriable" => $rejected_pr_inventoriables_count,
            "return_pr_inventoriable" => $return_invent_count,
            "Expense" => $pr_expense_count,
            "rejected_expense" => $rejected_pr_expense_count,
            "return_pr_expense" => $return_expense_count,
            "rejected_asset" => $rejected_pr_assets_count,
            "Job Order" => $pr_job_order_count,
            "rejected_job_order" => $rejected_pr_job_order_count,
        ];

        return GlobalFunction::responseFunction(
            Message::DISPLAY_COUNT,
            $result
        );
    }
}
