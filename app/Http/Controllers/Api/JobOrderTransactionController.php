<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use App\Models\JobItems;
use App\Models\JobOrder;
use App\Response\Message;
use App\Models\JobHistory;
use App\Models\JoPoOrders;
use App\Models\LogHistory;
use App\Models\POSettings;
use App\Models\JoPoHistory;
use App\Models\PoApprovers;
use App\Models\SetApprover;
use Illuminate\Http\Request;
use App\Models\JobOrderMinMax;
use App\Models\JOPOTransaction;
use App\Models\ApproverSettings;
use App\Functions\GlobalFunction;
use App\Models\JobOrderApprovers;
use Illuminate\Support\Facades\DB;
use App\Models\JobOrderTransaction;
use App\Http\Controllers\Controller;
use App\Http\Requests\PRViewRequest;
use App\Http\Resources\JoPoResource;
use App\Http\Resources\JobOrderResource;
use App\Models\JobOrderPurchaseOrderApprovers;
use App\Http\Requests\JobOrderTransaction\StoreRequest;
use App\Http\Requests\JobOrderTransaction\CancelRequest;
use App\Http\Requests\JobOrderTransaction\UpdateRequest;

class JobOrderTransactionController extends Controller
{
    public function index(PRViewRequest $request)
    {
        $status = $request->status;
        $job_order_request = JobOrderTransaction::with(
            "order",
            "order.assets",
            "approver_history",
            "log_history.users",
            "jo_po_transaction",
            "jo_po_transaction.jo_approver_history"
        )
            ->orderBy("rush", "desc")
            ->orderBy("updated_at", "desc")
            ->useFilters()
            ->dynamicPaginate();

        if ($job_order_request->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        JobOrderResource::collection($job_order_request);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $job_order_request
        );
    }
    public function show()
    {
    }
    public function store(StoreRequest $request)
    {
        $user_id = Auth()->user()->id;
        $requestor_deptartment_id = Auth()->user()->department_id;
        $requestor_department_unit_id = Auth()->user()->department_unit_id;
        $requestor_company_id = Auth()->user()->company_id;
        $requestor_business_id = Auth()->user()->business_unit_id;
        $requestor_location_id = Auth()->user()->location_id;
        $requestor_sub_unit_id = Auth()->user()->sub_unit_id;
        $equal_settings = false;
        $orders = $request->order;
        $dateToday = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");
        $for_po_id = $request->boolean("for_po_only") ? $user_id : null;
        $date_today = $request->boolean("for_po_only") ? $dateToday : null;
        $rush = $request->boolean("rush") ? $dateToday : null;
        $orders = $request->order;
        $current_year = date("Y");

        $sumOfTotalPrices = array_sum(array_column($orders, "total_price"));

        $amount_min_max = JobOrderMinMax::first();

        $direct_po = $sumOfTotalPrices <= $amount_min_max->amount_min;

        if ($direct_po) {
            $requestor_purchase_order_setting_id = GlobalFunction::job_request_purchase_order_requestor_setting_id(
                $requestor_company_id,
                $requestor_business_id,
                $requestor_deptartment_id
            );

            $charging_purchase_order_setting_id = GlobalFunction::job_request_purchase_order_charger_setting_id(
                $request->company_id,
                $request->business_unit_id,
                $request->department_id
            );

            $requestor_po_approvers = JobOrderPurchaseOrderApprovers::where(
                "jo_purchase_order_id",
                $requestor_purchase_order_setting_id->id
            )
                ->where("base_price", "<=", $sumOfTotalPrices)
                ->get();

            if (
                $requestor_purchase_order_setting_id->id !==
                $charging_purchase_order_setting_id->id
            ) {
                $charging_po_approvers = JobOrderPurchaseOrderApprovers::where(
                    "jo_purchase_order_id",
                    $charging_purchase_order_setting_id->id
                )->get();
            }
            DB::beginTransaction();

            try {
                $latest_jr = GlobalFunction::latest_jr($current_year);
                $new_number = $latest_jr
                    ? (int) explode("-", $latest_jr->jo_year_number_id)[2] + 1
                    : 1;

                $latest_jr_number =
                    JobOrderTransaction::withTrashed()->max("jo_number") ?? 0;
                $increment = $latest_jr_number + 1;

                $pr_year_number_id =
                    $current_year .
                    "-JR-" .
                    str_pad($new_number, 3, "0", STR_PAD_LEFT);

                $common_data = [
                    "outside_labor" => $request->outside_labor,
                    "cap_ex" => $request->cap_ex,
                    "date_needed" => $request->date_needed,
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
                    "asset" => $request->asset,
                    "description" => $request->description,
                ];

                $job_order_request = new JobOrderTransaction(
                    array_merge($common_data, [
                        "jo_year_number_id" => $pr_year_number_id,
                        "jo_number" => $increment,
                        "jo_description" => $request->jo_description,
                        "module_name" => "Job Order",
                        "status" => "Approved",
                        "layer" => "1",
                        "rush" => $rush,
                        "helpdesk_id" => $request->helpdesk_id,
                        "for_po_only" => $date_today,
                        "for_po_only_id" => $for_po_id,
                        "approved_at" => $dateToday,
                    ])
                );

                $job_order_request->save();

                $job_items = [];

                foreach ($orders as $index => $values) {
                    $attachments = $request["order"][$index]["attachment"];

                    if (!empty($attachments)) {
                        $filenames = [];
                        foreach ($attachments as $fileIndex => $file) {
                            $originalFilename = basename($file);
                            $info = pathinfo($originalFilename);
                            $filenameOnly = $info["filename"];
                            $extension = $info["extension"];
                            $filename = "{$filenameOnly}_jo_id_{$job_order_request->id}_item_{$index}_file_{$fileIndex}.{$extension}";
                            $filenames[] = $filename;
                        }
                        $filenames = json_encode($filenames);
                    } else {
                        $filenames = $attachments;
                    }

                    $job_item = JobItems::create([
                        "jo_transaction_id" => $job_order_request->id,
                        "description" => $values["description"],
                        "uom_id" => $values["uom_id"],
                        "quantity" => $values["quantity"],
                        "unit_price" => $values["unit_price"],
                        "total_price" =>
                            $values["unit_price"] * $values["quantity"],
                        "remarks" => $values["remarks"],
                        "attachment" => $filenames,
                        "asset" => $values["asset"],
                        "asset_code" => $values["asset_code"],
                        "helpdesk_id" => $values["helpdesk_id"],
                    ]);

                    $job_items[] = $job_item;

                    LogHistory::create([
                        "activity" =>
                            "Job order purchase request ID: " .
                            $job_order_request->id .
                            " has been created by UID: " .
                            $user_id,
                        "jo_id" => $job_order_request->id,
                        "action_by" => $user_id,
                    ]);
                }

                // --- Purchase Order Creation ---
                $latest_po = GlobalFunction::latest_jo($current_year);

                $new_po_number = $latest_po
                    ? (int) explode("-", $latest_po->po_year_number_id)[2] + 1
                    : 1;

                $po_year_number_id =
                    $current_year .
                    "-JO-" .
                    str_pad($new_po_number, 3, "0", STR_PAD_LEFT);

                $jo_number = JOPOTransaction::withTrashed()
                    ->latest()
                    ->first();
                $po_increment = $jo_number ? $jo_number->id + 1 : 1;

                $job_order_po = new JOPOTransaction(
                    array_merge($common_data, [
                        "po_year_number_id" => $po_year_number_id,
                        "po_number" => $po_increment,
                        "jo_number" => $job_order_request->jo_number,
                        "po_description" => $request->jo_description,
                        "module_name" => $request->module_name,
                        "total_item_price" => $request->total_item_price,
                        "supplier_id" => $request->supplier_id,
                        "supplier_name" => $request->supplier_name,
                        "status" => "Pending",
                        "sgp" => $request->sgp,
                        "f1" => $request->f1,
                        "f2" => $request->f2,
                        "rush" => $request->rush,
                        "layer" => "1",
                    ])
                );

                $job_order_po->save();

                foreach ($orders as $index => $values) {
                    JoPoOrders::create([
                        "jo_po_id" => $job_order_po->id,
                        "jo_transaction_id" => $job_order_request->id,
                        "jo_item_id" => $job_items[$index]->id,
                        "description" => $values["description"],
                        "uom_id" => $values["uom_id"],
                        "unit_price" => $values["unit_price"],
                        "quantity" => $values["quantity"],
                        "quantity_serve" => 0,
                        "total_price" =>
                            $values["unit_price"] * $values["quantity"],
                        "attachment" => $values["attachment"],
                        "remarks" => $values["remarks"],
                        "asset" => $values["asset"],
                        "asset_code" => $values["asset_code"],
                        "helpdesk_id" => $values["helpdesk_id"],
                    ]);

                    JobItems::where("id", $job_items[$index]->id)->update([
                        "po_at" => $dateToday,
                        "purchase_order_id" => $job_order_po->id,
                    ]);
                }

                $layer_count = 1;

                if (
                    $requestor_purchase_order_setting_id->id ===
                    $charging_purchase_order_setting_id->id
                ) {
                    foreach ($requestor_po_approvers as $approver) {
                        JoPoHistory::create([
                            "jo_po_id" => $job_order_po->id,
                            "approver_id" => $approver->approver_id,
                            "approver_name" => $approver->approver_name,
                            "layer" => $layer_count++,
                        ]);
                    }
                } else {
                    foreach ($requestor_po_approvers as $approver) {
                        JoPoHistory::create([
                            "jo_po_id" => $job_order_po->id,
                            "approver_id" => $approver->approver_id,
                            "approver_name" => $approver->approver_name,
                            "layer" => $layer_count++,
                        ]);
                    }

                    foreach ($charging_po_approvers as $approver) {
                        JoPoHistory::create([
                            "jo_po_id" => $job_order_po->id,
                            "approver_id" => $approver->approver_id,
                            "approver_name" => $approver->approver_name,
                            "layer" => $layer_count++,
                        ]);
                    }
                }

                LogHistory::create([
                    "activity" =>
                        "Job order purchase order ID: " .
                        $job_order_po->id .
                        " has been created by UID: " .
                        $user_id,
                    "jo_po_id" => $job_order_po->id,
                    "action_by" => $user_id,
                ]);

                DB::commit();

                return GlobalFunction::save(
                    Message::PURCHASE_REQUEST_AND_ORDER_SAVE,
                    [
                        "pr" => new JobOrderResource($job_order_request),
                        "po" => new JoPoResource($job_order_po),
                    ]
                );
            } catch (\Exception $e) {
                DB::rollback();

                // Context data specific to this operation
                $contextData = [
                    "user_id" => $user_id,
                    "business_unit_id" => $request->business_unit_id ?? null,
                    "company_id" => $request->company_id ?? null,
                    "department_id" => $request->department_id ?? null,
                    "department_unit" => $request->department_unit_id ?? null,
                    "location" => $request->location ?? null,
                    "sub_unit_id" => $request->sub_unit_id ?? null,
                    "operation" => "job_order_store",
                ];

                // Use the global error handler
                return GlobalFunction::error($e, $contextData);
            }
        } else {
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
            )->get();

            $requestor_purchase_order_setting_id = GlobalFunction::job_request_purchase_order_requestor_setting_id(
                $requestor_company_id,
                $requestor_business_id,
                $requestor_deptartment_id
            );

            $charging_purchase_order_setting_id = GlobalFunction::job_request_purchase_order_charger_setting_id(
                $request->company_id,
                $request->business_unit_id,
                $request->department_id
            );

            $requestor_po_approvers = JobOrderPurchaseOrderApprovers::where(
                "jo_purchase_order_id",
                $requestor_purchase_order_setting_id->id
            )
                ->where("base_price", "<=", $sumOfTotalPrices)
                ->get();

            if ($requestor_setting_id->id !== $charging_setting_id->id) {
                $charging_approvers = JobOrderApprovers::where(
                    "job_order_id",
                    $charging_setting_id->id
                )->get();
            }

            if (
                $requestor_purchase_order_setting_id->id !==
                $charging_purchase_order_setting_id->id
            ) {
                return $charging_po_approvers = JobOrderPurchaseOrderApprovers::where(
                    "jo_purchase_order_id",
                    $charging_purchase_order_setting_id->id
                )->get();
            }

            DB::beginTransaction();

            try {
                $latest_jr = GlobalFunction::latest_jr($current_year);
                $new_number = $latest_jr
                    ? (int) explode("-", $latest_jr->jo_year_number_id)[2] + 1
                    : 1;

                $latest_jr_number =
                    JobOrderTransaction::withTrashed()->max("jo_number") ?? 0;
                $increment = $latest_jr_number + 1;

                $pr_year_number_id =
                    $current_year .
                    "-JR-" .
                    str_pad($new_number, 3, "0", STR_PAD_LEFT);

                $common_data = [
                    "outside_labor" => $request->outside_labor,
                    "cap_ex" => $request->cap_ex,
                    "date_needed" => $request->date_needed,
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
                    "asset" => $request->asset,
                    "description" => $request->description,
                ];

                $job_order_request = new JobOrderTransaction(
                    array_merge($common_data, [
                        "jo_year_number_id" => $pr_year_number_id,
                        "jo_number" => $increment,
                        "jo_description" => $request->jo_description,
                        "module_name" => "Job Order",
                        "status" => "Pending",
                        "layer" => "1",
                        "rush" => $rush,
                        "helpdesk_id" => $request->helpdesk_id,
                        "for_po_only" => $date_today,
                        "for_po_only_id" => $for_po_id,
                    ])
                );

                $job_order_request->save();

                $job_items = [];

                foreach ($orders as $index => $values) {
                    $attachments = $request["order"][$index]["attachment"];

                    if (!empty($attachments)) {
                        $filenames = [];
                        foreach ($attachments as $fileIndex => $file) {
                            $originalFilename = basename($file);
                            $info = pathinfo($originalFilename);
                            $filenameOnly = $info["filename"];
                            $extension = $info["extension"];
                            $filename = "{$filenameOnly}_jo_id_{$job_order_request->id}_item_{$index}_file_{$fileIndex}.{$extension}";
                            $filenames[] = $filename;
                        }
                        $filenames = json_encode($filenames);
                    } else {
                        $filenames = $attachments;
                    }

                    $job_item = JobItems::create([
                        "jo_transaction_id" => $job_order_request->id,
                        "description" => $values["description"],
                        "uom_id" => $values["uom_id"],
                        "quantity" => $values["quantity"],
                        "unit_price" => $values["unit_price"],
                        "total_price" =>
                            $values["unit_price"] * $values["quantity"],
                        "remarks" => $values["remarks"],
                        "attachment" => $filenames,
                        "asset" => $values["asset"],
                        "asset_code" => $values["asset_code"],
                        "helpdesk_id" => $values["helpdesk_id"],
                    ]);

                    $job_items[] = $job_item;
                }

                $layer_count = 1;

                if ($requestor_setting_id->id === $charging_setting_id->id) {
                    foreach ($requestor_approvers as $approver) {
                        JobHistory::create([
                            "jo_id" => $job_order_request->id,
                            "approver_id" => $approver->approver_id,
                            "approver_name" => $approver->approver_name,
                            "layer" => $layer_count++,
                        ]);
                    }
                } else {
                    foreach ($requestor_approvers as $approver) {
                        JobHistory::create([
                            "jo_id" => $job_order_request->id,
                            "approver_id" => $approver->approver_id,
                            "approver_name" => $approver->approver_name,
                            "layer" => $layer_count++,
                        ]);
                    }

                    foreach ($charging_approvers as $approver) {
                        JobHistory::create([
                            "jo_id" => $job_order_request->id,
                            "approver_id" => $approver->approver_id,
                            "approver_name" => $approver->approver_name,
                            "layer" => $layer_count++,
                        ]);
                    }
                }

                LogHistory::create([
                    "activity" =>
                        "Job order purchase request ID: " .
                        $job_order_request->id .
                        " has been created by UID: " .
                        $user_id,
                    "jo_id" => $job_order_request->id,
                    "action_by" => $user_id,
                ]);

                // --- Purchase Order Creation ---
                $latest_po = GlobalFunction::latest_jo($current_year);

                $new_po_number = $latest_po
                    ? (int) explode("-", $latest_po->po_year_number_id)[2] + 1
                    : 1;

                $po_year_number_id =
                    $current_year .
                    "-JO-" .
                    str_pad($new_po_number, 3, "0", STR_PAD_LEFT);

                $jo_number = JOPOTransaction::withTrashed()
                    ->latest()
                    ->first();
                $po_increment = $jo_number ? $jo_number->id + 1 : 1;

                $job_order_po = new JOPOTransaction(
                    array_merge($common_data, [
                        "po_year_number_id" => $po_year_number_id,
                        "po_number" => $po_increment,
                        "jo_number" => $job_order_request->jo_number,
                        "po_description" => $request->jo_description,
                        "module_name" => $request->module_name,
                        "total_item_price" => $request->total_item_price,
                        "supplier_id" => $request->supplier_id,
                        "supplier_name" => $request->supplier_name,
                        "status" => "Pending",
                        "sgp" => $request->sgp,
                        "f1" => $request->f1,
                        "f2" => $request->f2,
                        "rush" => $request->rush,
                        "layer" => "1",
                    ])
                );

                $job_order_po->save();

                foreach ($orders as $index => $values) {
                    JoPoOrders::create([
                        "jo_po_id" => $job_order_po->id,
                        "jo_transaction_id" => $job_order_request->id,
                        "jo_item_id" => $job_items[$index]->id,
                        "description" => $values["description"],
                        "uom_id" => $values["uom_id"],
                        "unit_price" => $values["unit_price"],
                        "quantity" => $values["quantity"],
                        "quantity_serve" => 0,
                        "total_price" =>
                            $values["unit_price"] * $values["quantity"],
                        "attachment" => $values["attachment"],
                        "remarks" => $values["remarks"],
                        "asset" => $values["asset"],
                        "asset_code" => $values["asset_code"],
                        "helpdesk_id" => $values["helpdesk_id"],
                    ]);

                    JobItems::where("id", $job_items[$index]->id)->update([
                        "po_at" => $dateToday,
                        "purchase_order_id" => $job_order_po->id,
                    ]);
                }

                $layer_count = 1;

                if (
                    $requestor_purchase_order_setting_id->id ===
                    $charging_purchase_order_setting_id->id
                ) {
                    foreach ($requestor_po_approvers as $approver) {
                        JoPoHistory::create([
                            "jo_po_id" => $job_order_po->id,
                            "approver_id" => $approver->approver_id,
                            "approver_name" => $approver->approver_name,
                            "layer" => $layer_count++,
                        ]);
                    }
                } else {
                    foreach ($requestor_po_approvers as $approver) {
                        JoPoHistory::create([
                            "jo_po_id" => $job_order_po->id,
                            "approver_id" => $approver->approver_id,
                            "approver_name" => $approver->approver_name,
                            "layer" => $layer_count++,
                        ]);
                    }

                    foreach ($charging_po_approvers as $approver) {
                        JoPoHistory::create([
                            "jo_po_id" => $job_order_po->id,
                            "approver_id" => $approver->approver_id,
                            "approver_name" => $approver->approver_name,
                            "layer" => $layer_count++,
                        ]);
                    }
                }

                LogHistory::create([
                    "activity" =>
                        "Job order purchase order ID: " .
                        $job_order_po->id .
                        " has been created by UID: " .
                        $user_id,
                    "jo_po_id" => $job_order_po->id,
                    "action_by" => $user_id,
                ]);

                DB::commit();

                return GlobalFunction::save(
                    Message::PURCHASE_REQUEST_AND_ORDER_SAVE,
                    [
                        "pr" => new JobOrderResource($job_order_request),
                        "po" => new JoPoResource($job_order_po),
                    ]
                );
            } catch (\Exception $e) {
                DB::rollback();

                // Context data specific to this operation
                $contextData = [
                    "user_id" => $user_id,
                    "business_unit_id" => $request->business_unit_id ?? null,
                    "company_id" => $request->company_id ?? null,
                    "department_id" => $request->department_id ?? null,
                    "department_unit" => $request->department_unit_id ?? null,
                    "location" => $request->location ?? null,
                    "sub_unit_id" => $request->sub_unit_id ?? null,
                    "operation" => "job_order_store",
                ];

                // Use the global error handler
                return GlobalFunction::error($e, $contextData);
            }
        }
    }

    public function update(UpdateRequest $request, $id)
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

        $current_jr = JobOrderTransaction::where("id", $id)->first();
        $current_po = JOPOTransaction::where(
            "jo_number",
            $current_jr->jo_number
        )->first();

        // Calculate new total price
        $newTotalPrice = array_sum(array_column($orders, "total_price"));

        // Get current total price
        $currentItems = JobItems::where("jo_transaction_id", $id)->get();
        $currentTotalPrice = $currentItems->sum("total_price");

        $amount_min_max = JobOrderMinMax::first();
        $is_price_increased = $newTotalPrice > $currentTotalPrice;
        $was_direct_po = $currentTotalPrice <= $amount_min_max->amount_min;
        $will_be_direct_po = $newTotalPrice <= $amount_min_max->amount_min;

        DB::beginTransaction();

        try {
            // Update JR common data
            $common_data = [
                "outside_labor" => $request->outside_labor,
                "cap_ex" => $request->cap_ex,
                "date_needed" => $request->date_needed,
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
                "asset" => $request->asset,
                "description" => $request->description,
            ];

            $was_cancelled =
                $current_jr->status === "Cancelled" ||
                $current_po->status === "Cancelled";

            // Always update both JR and PO with common data
            $current_jr->update($common_data);
            $current_po->update($common_data);

            if ($was_cancelled) {
                $is_resubmission = true;
                if ($will_be_direct_po) {
                    // If it's a direct PO, set to approved immediately
                    $current_jr->update([
                        "status" => "Approved",
                        "approved_at" => $dateToday,
                        "cancelled_at" => null,
                    ]);

                    $current_po->update([
                        "status" => "Approved",
                        "approved_at" => $dateToday,
                        "cancelled_at" => null,
                    ]);
                } else {
                    // Reset to pending for regular approval flow
                    $current_jr->update([
                        "status" => "Pending",
                        "approved_at" => null,
                        "cancelled_at" => null,
                    ]);

                    $current_po->update([
                        "status" => "Pending",
                        "approved_at" => null,
                        "cancelled_at" => null,
                    ]);

                    // Reset all approver statuses
                    JobHistory::where("jo_id", $current_jr->id)->update([
                        "approved_at" => null,
                    ]);

                    JoPoHistory::where("jo_po_id", $current_po->id)->update([
                        "approved_at" => null,
                    ]);
                }
            } else {
                if ($was_direct_po) {
                    if (!$will_be_direct_po) {
                        // Change from direct PO to regular flow
                        $current_jr->update([
                            "status" => "Pending",
                            "approved_at" => null,
                            "cancelled_at" => null,
                        ]);

                        $current_po->update([
                            "status" => "Pending",
                            "approved_at" => null,
                            "cancelled_at" => null,
                        ]);

                        // Add approvers
                        $requestor_setting_id = GlobalFunction::job_request_requestor_setting_id(
                            $requestor_company_id,
                            $requestor_business_id,
                            $requestor_deptartment_id,
                            $requestor_department_unit_id,
                            $requestor_sub_unit_id,
                            $requestor_location_id
                        );

                        $charging_setting_id = GlobalFunction::job_request_charger_setting_id(
                            $requestor_company_id,
                            $requestor_business_id,
                            $requestor_deptartment_id,
                            $requestor_department_unit_id,
                            $requestor_sub_unit_id,
                            $requestor_location_id
                        );

                        $requestor_approvers = JobOrderApprovers::where(
                            "job_order_id",
                            $requestor_setting_id->id
                        )->get();
                        $charging_approvers =
                            $requestor_setting_id->id !==
                            $charging_setting_id->id
                                ? JobOrderApprovers::where(
                                    "job_order_id",
                                    $charging_setting_id->id
                                )->get()
                                : collect([]);

                        $layer = 1;

                        // Add JR approvers or update if they exist
                        foreach ($requestor_approvers as $approver) {
                            JobHistory::updateOrCreate(
                                [
                                    "jo_id" => $current_jr->id,
                                    "approver_id" => $approver->approver_id,
                                ],
                                [
                                    "approver_name" => $approver->approver_name,
                                    "layer" => $layer++,
                                ]
                            );
                        }

                        foreach ($charging_approvers as $approver) {
                            JobHistory::updateOrCreate(
                                [
                                    "jo_id" => $current_jr->id,
                                    "approver_id" => $approver->approver_id,
                                ],
                                [
                                    "approver_name" => $approver->approver_name,
                                    "layer" => $layer++,
                                ]
                            );
                        }

                        // Add or update PO approvers with the same layering
                        $layer = 1;
                        foreach ($requestor_approvers as $approver) {
                            JoPoHistory::updateOrCreate(
                                [
                                    "jo_po_id" => $current_po->id,
                                    "approver_id" => $approver->approver_id,
                                ],
                                [
                                    "approver_name" => $approver->approver_name,
                                    "layer" => $layer++,
                                ]
                            );
                        }

                        foreach ($charging_approvers as $approver) {
                            JoPoHistory::updateOrCreate(
                                [
                                    "jo_po_id" => $current_po->id,
                                    "approver_id" => $approver->approver_id,
                                ],
                                [
                                    "approver_name" => $approver->approver_name,
                                    "layer" => $layer++,
                                ]
                            );
                        }
                    }
                } else {
                    if ($is_price_increased) {
                        // Reset approval dates due to price increase
                        JobHistory::where("jo_id", $current_jr->id)->update([
                            "approved_at" => null,
                        ]);
                        JoPoHistory::where("jo_po_id", $current_po->id)->update(
                            [
                                "approved_at" => null,
                            ]
                        );

                        // Change from direct PO to regular flow
                        $current_jr->update([
                            "status" => "Pending",
                            "approved_at" => null,
                        ]);

                        $current_po->update([
                            "status" => "Pending",
                            "approved_at" => null,
                        ]);

                        $existing_jr_approvers = JobHistory::where(
                            "jo_id",
                            $current_jr->id
                        )->get();
                        $existing_po_approvers = JoPoHistory::where(
                            "jo_po_id",
                            $current_po->id
                        )->get();

                        $requestor_purchase_order_setting_id = GlobalFunction::job_request_purchase_order_requestor_setting_id(
                            $requestor_company_id,
                            $requestor_business_id,
                            $requestor_deptartment_id
                        );

                        // Update existing approvers' approval if they still meet the threshold
                        foreach ($existing_jr_approvers as $approver) {
                            $approver_threshold = JobOrderPurchaseOrderApprovers::where(
                                "jo_purchase_order_id",
                                $requestor_purchase_order_setting_id->id
                            )
                                ->where("approver_id", $approver->approver_id)
                                ->where("base_price", "<=", $newTotalPrice)
                                ->first();

                            if ($approver_threshold) {
                                $approver->update([
                                    "approved_at" => $dateToday,
                                ]);
                            }
                        }

                        foreach ($existing_po_approvers as $approver) {
                            $approver_threshold = JobOrderPurchaseOrderApprovers::where(
                                "jo_purchase_order_id",
                                $requestor_purchase_order_setting_id->id
                            )
                                ->where("approver_id", $approver->approver_id)
                                ->where("base_price", "<=", $newTotalPrice)
                                ->first();

                            if ($approver_threshold) {
                                $approver->update([
                                    "approved_at" => $dateToday,
                                ]);
                            }
                        }

                        // Add new approvers if price exceeds current approvers' thresholds
                        $new_po_approvers = JobOrderPurchaseOrderApprovers::where(
                            "jo_purchase_order_id",
                            $requestor_purchase_order_setting_id->id
                        )
                            ->where("base_price", "<=", $newTotalPrice)
                            ->whereNotIn(
                                "approver_id",
                                array_merge(
                                    $existing_jr_approvers
                                        ->pluck("approver_id")
                                        ->toArray(),
                                    $existing_po_approvers
                                        ->pluck("approver_id")
                                        ->toArray()
                                )
                            )
                            ->get();

                        $next_layer =
                            max(
                                $existing_po_approvers
                                    ->pluck("layer")
                                    ->toArray()
                            ) + 1;

                        foreach ($new_po_approvers as $approver) {
                            JoPoHistory::create([
                                "jo_po_id" => $current_po->id,
                                "approver_id" => $approver->approver_id,
                                "approver_name" => $approver->approver_name,
                                "layer" => $next_layer++,
                            ]);
                        }
                    }
                }
            }

            // Update items
            $existingItems = JobItems::where("jo_transaction_id", $id)->get();

            foreach ($orders as $index => $values) {
                $existingItem = $existingItems->firstWhere("id", $values["id"]);

                if ($existingItem) {
                    // Update existing item
                    $attachments = $request["order"][$index]["attachment"];

                    if (!empty($attachments)) {
                        $filenames = [];
                        foreach ($attachments as $fileIndex => $file) {
                            $originalFilename = basename($file);
                            $info = pathinfo($originalFilename);
                            $filenameOnly = $info["filename"];
                            $extension = $info["extension"];
                            $filename = "{$filenameOnly}_jo_id_{$current_jr->id}_item_{$index}_file_{$fileIndex}.{$extension}";
                            $filenames[] = $filename;
                        }
                        $filenames = json_encode($filenames);
                    } else {
                        $filenames = $existingItem->attachment;
                    }

                    $existingItem->update([
                        "description" => $values["description"],
                        "uom_id" => $values["uom_id"],
                        "quantity" => $values["quantity"],
                        "unit_price" => $values["unit_price"],
                        "total_price" =>
                            $values["unit_price"] * $values["quantity"],
                        "remarks" => $values["remarks"],
                        "attachment" => $filenames,
                        "asset" => $values["asset"],
                        "asset_code" => $values["asset_code"],
                        "helpdesk_id" => $values["helpdesk_id"],
                    ]);

                    // Update the corresponding JoPoOrders entry
                    $joPOOrder = JoPoOrders::where(
                        "jo_item_id",
                        $existingItem->id
                    )->first();
                    $joPOOrder->update([
                        "description" => $values["description"],
                        "uom_id" => $values["uom_id"],
                        "unit_price" => $values["unit_price"],
                        "quantity" => $values["quantity"],
                        "total_price" =>
                            $values["unit_price"] * $values["quantity"],
                        "attachment" => $filenames,
                        "remarks" => $values["remarks"],
                        "asset" => $values["asset"],
                        "asset_code" => $values["asset_code"],
                        "helpdesk_id" => $values["helpdesk_id"],
                    ]);
                } else {
                    // Create new item
                    $attachments = $request["order"][$index]["attachment"];

                    if (!empty($attachments)) {
                        $filenames = [];
                        foreach ($attachments as $fileIndex => $file) {
                            $originalFilename = basename($file);
                            $info = pathinfo($originalFilename);
                            $filenameOnly = $info["filename"];
                            $extension = $info["extension"];
                            $filename = "{$filenameOnly}_jo_id_{$current_jr->id}_item_{$index}_file_{$fileIndex}.{$extension}";
                            $filenames[] = $filename;
                        }
                        $filenames = json_encode($filenames);
                    } else {
                        $filenames = null;
                    }

                    $jobItem = JobItems::create([
                        "jo_transaction_id" => $current_jr->id,
                        "po_at" => $dateToday,
                        "purchase_order_id" => $id,
                        "description" => $values["description"],
                        "uom_id" => $values["uom_id"],
                        "quantity" => $values["quantity"],
                        "unit_price" => $values["unit_price"],
                        "total_price" =>
                            $values["unit_price"] * $values["quantity"],
                        "remarks" => $values["remarks"],
                        "attachment" => $filenames,
                        "asset" => $values["asset"],
                        "asset_code" => $values["asset_code"],
                        "helpdesk_id" => $values["helpdesk_id"],
                        "po_at" => $dateToday,
                        "purchase_order_id" => $current_po->id,
                    ]);

                    JoPoOrders::create([
                        "jo_po_id" => $current_po->id,
                        "jo_transaction_id" => $current_jr->id,
                        "jo_item_id" => $jobItem->id,
                        "description" => $values["description"],
                        "uom_id" => $values["uom_id"],
                        "unit_price" => $values["unit_price"],
                        "quantity" => $values["quantity"],
                        "quantity_serve" => 0,
                        "total_price" =>
                            $values["unit_price"] * $values["quantity"],
                        "attachment" => $filenames,
                        "remarks" => $values["remarks"],
                        "asset" => $values["asset"],
                        "asset_code" => $values["asset_code"],
                        "helpdesk_id" => $values["helpdesk_id"],
                    ]);
                }
            }

            LogHistory::create([
                "activity" => $is_resubmission
                    ? "Job order purchase request ID: {$current_jr->id} has been resubmitted by UID: {$user_id}"
                    : "Job order purchase request ID: {$current_jr->id} has been updated by UID: {$user_id}",
                "jo_id" => $current_jr->id,
                "action_by" => $user_id,
            ]);

            LogHistory::create([
                "activity" => $is_resubmission
                    ? "Job order purchase order ID: {$current_po->id} has been resubmitted by UID: {$user_id}"
                    : "Job order purchase order ID: {$current_po->id} has been updated by UID: {$user_id}",
                "jo_po_id" => $current_po->id,
                "action_by" => $user_id,
            ]);

            DB::commit();

            return GlobalFunction::save(
                Message::PURCHASE_REQUEST_AND_ORDER_UPDATE,
                [
                    "pr" => new JobOrderResource($current_jr),
                    "po" => new JoPoResource($current_po),
                ]
            );
        } catch (\Exception $e) {
            DB::rollback();

            $contextData = [
                "user_id" => $user_id,
                "business_unit_id" => $request->business_unit_id ?? null,
                "company_id" => $request->company_id ?? null,
                "department_id" => $request->department_id ?? null,
                "department_unit" => $request->department_unit_id ?? null,
                "location" => $request->location ?? null,
                "sub_unit_id" => $request->sub_unit_id ?? null,
                "operation" => "job_order_update",
            ];

            return GlobalFunction::error($e, $contextData);
        }
    }

    public function destroy()
    {
    }

    public function cancel_jo(CancelRequest $request, $id)
    {
        $user = Auth()->user()->id;
        // Fetch the transaction by ID
        $transaction = JobOrderTransaction::where("id", $id)->first();

        if (!$transaction) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $po_id = JOPOTransaction::where("jo_number", $transaction->id)->first();

        if ($transaction && $transaction->status === "Cancelled") {
            return GlobalFunction::invalid(Message::CANCELLED_ALREADY);
        }

        // Update status to 'Cancelled'
        $transaction->update([
            "status" => "Cancelled",
            "approved_at" => null,
            "cancelled_at" => now(),
            "reason" => $request->reason,
        ]);

        // Handle related PO or PR transactions if needed
        if ($transaction->id) {
            $cancel_po = JOPOTransaction::where(
                "jo_number",
                $transaction->id
            )->update([
                "status" => "Cancelled",
                "approved_at" => null,
                "cancelled_at" => now(),
                "reason" => $request->reason,
            ]);
        }

        if ($transaction->id) {
            JobOrderTransaction::where("id", $transaction->id)->update([
                "status" => "Cancelled",
            ]);
        }

        $activityDescription =
            "Job order purchase request ID:" .
            $id .
            " has been cancelled by UID: " .
            $user .
            " Reason: " .
            $request->reason;

        LogHistory::create([
            "activity" => $activityDescription,
            "jo_id" => $id,
            "action_by" => $user,
        ]);

        $activityDescription =
            "Job order purchase order ID:" .
            $id .
            " has been cancelled by UID: " .
            $user .
            " Reason: " .
            $request->reason;

        LogHistory::create([
            "activity" => $activityDescription,
            "jo_po_id" => $po_id->id,
            "action_by" => $user,
        ]);

        return GlobalFunction::responseFunction(
            Message::CANCELLED,
            $transaction
        );
    }

    public function voided_jo(CancelRequest $request, $id)
    {
        $user = Auth()->user()->id;
        // Fetch the transaction by ID
        $transaction = JobOrderTransaction::findOrFail($id);

        $po_id = JOPOTransaction::where("jo_number", $transaction->id)->first();

        if ($transaction && $transaction->status === "Voided") {
            return GlobalFunction::invalid(Message::VOIDED_ALREADY);
        }

        // Update status to 'Cancelled'
        $transaction->update([
            "cancelled_at" => null,
            "voided_at" => now(),
            "reason" => $request->reason,
        ]);

        // Handle related PO or PR transactions if needed
        if ($transaction->id) {
            $cancel_po = JOPOTransaction::where(
                "jo_number",
                $transaction->id
            )->update([
                "cancelled_at" => null,
                "status" => "Voided",
                "voided_at" => now(),
                "reason" => $request->reason,
            ]);
        }

        if ($transaction->id) {
            JobOrderTransaction::where("id", $transaction->id)->update([
                "status" => "Voided",
            ]);
        }

        $activityDescription =
            "Job order purchase request ID:" .
            $id .
            " has been voided by UID: " .
            $user .
            " Reason: " .
            $request->reason;

        LogHistory::create([
            "activity" => $activityDescription,
            "jo_id" => $id,
            "action_by" => $user,
        ]);

        $activityDescription =
            "Job order purchase order ID:" .
            $id .
            " has been voided by UID: " .
            $user .
            " Reason: " .
            $request->reason;

        LogHistory::create([
            "activity" => $activityDescription,
            "jo_po_id" => $po_id->id,
            "action_by" => $user,
        ]);

        return GlobalFunction::responseFunction(Message::VOIDED, $transaction);
    }

    public function resubmit()
    {
    }

    public function pa_jo_badge()
    {
    }
}
