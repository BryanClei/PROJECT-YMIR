<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use App\Models\JobItems;
use App\Models\JobOrder;
use App\Models\JrDrafts;
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
        $user_id = Auth()->user()->id;

        $status = $request->status;
        $job_order_request = JobOrderTransaction::with(
            "order.uom",
            "order.assets",
            "approver_history",
            "log_history.users",
            "jo_po_transaction",
            "jo_po_transaction.jo_approver_history",
            "jo_po_transaction.jo_po_orders.rr_orders.jo_rr_transaction"
        )
            ->where("user_id", $user_id)
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
        $requestor_one_charging_sync_id = Auth()->user()->one_charging_sync_id;

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

        if (is_null($amount_min_max)) {
            return GloblaFunction::notFound(Message::NO_MIN_MAX);
        }

        $direct_po = $sumOfTotalPrices <= $amount_min_max->amount_min;
        $pr_draft = $request->jr_draft_id ? $request->jr_draft_id : null;

        if ($direct_po) {
            $requestor_setting_id = GlobalFunction::job_request_requestor_setting_id(
                $requestor_one_charging_sync_id
            );

            if (!$requestor_setting_id) {
                return GlobalFunction::invalid(
                    Message::NO_APPROVERS_SETTINGS_YET
                );
            }

            $charging_setting_id = GlobalFunction::job_request_charger_setting_id(
                $request->one_charging_sync_id
            );

            if (!$charging_setting_id) {
                return GlobalFunction::invalid(
                    Message::NO_APPROVERS_SETTINGS_YET
                );
            }

            $requestor_approvers = JobOrderApprovers::where(
                "job_order_id",
                $requestor_setting_id->id
            )
                ->where("layer", "1")
                ->get();

            $final_requestor_approvers = $requestor_approvers->take(1);

            if ($requestor_setting_id->id !== $charging_setting_id->id) {
                $charging_approvers = JobOrderApprovers::where(
                    "job_order_id",
                    $charging_setting_id->id
                )
                    ->where("layer", "1")
                    ->get();

                $final_charging_approvers = $charging_approvers->take(1);
            }

            $final_charging_approvers;

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
                "one_charging_id" => $request->one_charging_id,
                "one_charging_sync_id" => $request->one_charging_sync_id,
                "one_charging_code" => $request->one_charging_code,
                "one_charging_name" => $request->one_charging_name,
                "business_unit_id" => $request->business_unit_id,
                "business_unit_code" => $request->business_unit_code,
                "business_unit_name" => $request->business_unit_name,
                "company_id" => $request->company_id,
                "company_code" => $request->company_code,
                "company_name" => $request->company_name,
                "department_id" => $request->department_id,
                "department_code" => $request->department_code,
                "department_name" => $request->department_name,
                "department_unit_id" => $request->department_unit_id,
                "department_unit_code" => $request->department_unit_code,
                "department_unit_name" => $request->department_unit_name,
                "location_id" => $request->location_id,
                "location_code" => $request->location_code,
                "location_name" => $request->location_name,
                "sub_unit_id" => $request->sub_unit_id,
                "sub_unit_code" => $request->sub_unit_code,
                "sub_unit_name" => $request->sub_unit_name,
                "account_title_id" => $request->account_title_id,
                "account_title_name" => $request->account_title_name,
                "asset" => $request->asset,
                "description" => $request->description,
                "ship_to_id" => $request->ship_to_id,
                "ship_to_name" => $request->ship_to_name,
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
                    "supplier_id" => $request->supplier_id,
                    "supplier_name" => $request->supplier_name,
                    "approved_at" => $dateToday,
                    "direct_po" => $dateToday,
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
                        $filename = "{$filenameOnly}_jr_id_{$job_order_request->id}_item_{$index}_file_{$fileIndex}.{$extension}";
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

            LogHistory::create([
                "activity" =>
                    "Job order purchase request ID: " .
                    $job_order_request->id .
                    " has been created by UID: " .
                    $user_id,
                "jo_id" => $job_order_request->id,
                "action_by" => $user_id,
            ]);

            $latest_po = GlobalFunction::latest_jo($current_year);

            $new_po_number = $latest_po
                ? (int) explode("-", $latest_po->po_year_number_id)[2] + 1
                : 1;

            $po_year_number_id =
                $current_year .
                "-JO-" .
                str_pad($new_po_number, 3, "0", STR_PAD_LEFT);

            $latest_po_number =
                JOPOTransaction::withTrashed()->max("po_number") ?? 0;
            $po_increment = $latest_po_number + 1;

            $job_order_po = new JOPOTransaction(
                array_merge($common_data, [
                    "po_year_number_id" => $po_year_number_id,
                    "po_number" => $po_increment,
                    "jo_number" => $job_order_request->id,
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
                    "direct_po" => $dateToday,
                    "helpdesk_id" => $request->helpdesk_id,
                    "layer" => "1",
                ])
            );

            $job_order_po->save();

            foreach ($orders as $index => $values) {
                $attachments = $request["order"][$index]["attachment"];

                if (!empty($attachments)) {
                    $filenames = [];
                    foreach ($attachments as $fileIndex => $file) {
                        $originalFilename = basename($file);
                        $info = pathinfo($originalFilename);
                        $filenameOnly = $info["filename"];
                        $extension = $info["extension"];
                        $filename = "{$filenameOnly}_jr_id_{$job_order_request->id}_item_{$index}_file_{$fileIndex}.{$extension}";
                        $filenames[] = $filename;
                    }
                    $filenames = $filenames;
                } else {
                    $filenames = $attachments;
                }

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
                    "attachment" => $filenames,
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

            if ($requestor_setting_id->id === $charging_setting_id->id) {
                foreach ($final_requestor_approvers as $approver) {
                    JoPoHistory::create([
                        "jo_po_id" => $job_order_po->id,
                        "approver_type" => "service provider",
                        "approver_id" => $approver->approver_id,
                        "approver_name" => $approver->approver_name,
                        "layer" => $layer_count++,
                    ]);
                }
            } else {
                foreach ($final_requestor_approvers as $approver) {
                    JoPoHistory::create([
                        "jo_po_id" => $job_order_po->id,
                        "approver_type" => "service provider",
                        "approver_id" => $approver->approver_id,
                        "approver_name" => $approver->approver_name,
                        "layer" => $layer_count++,
                    ]);
                }

                foreach ($final_charging_approvers as $approver) {
                    JoPoHistory::create([
                        "jo_po_id" => $job_order_po->id,
                        "approver_type" => "charging",
                        "approver_id" => $approver->approver_id,
                        "approver_name" => $approver->approver_name,
                        "layer" => $layer_count++,
                    ]);
                }
            }

            if ($pr_draft) {
                $draft = JrDrafts::find($pr_draft);

                $draft->update([
                    "status" => "Submitted",
                ]);
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

            return GlobalFunction::save(
                Message::PURCHASE_REQUEST_AND_ORDER_SAVE,
                [
                    "pr" => new JobOrderResource($job_order_request),
                    "po" => new JoPoResource($job_order_po),
                ]
            );
        } else {
            $charging_setting_id = JobOrder::where(
                "one_charging_sync_id",
                $request->one_charging_sync_id
            )->first();

            if (!$charging_setting_id) {
                return GlobalFunction::invalid(
                    Message::NO_APPROVERS_SETTINGS_YET
                );
            }

            $charging_approvers = JobOrderApprovers::where(
                "job_order_id",
                $charging_setting_id->id
            )
                ->latest()
                ->get();

            if (!$charging_approvers) {
                return GlobalFunction::invalid(
                    Message::NO_APPROVERS_SETTINGS_YET
                );
            }

            $fixed_charging_approvers = $charging_approvers->take(2);

            $price_based_charging_approvers = $charging_approvers
                ->slice(2)
                ->filter(function ($approver) use ($sumOfTotalPrices) {
                    return $approver->base_price <= $sumOfTotalPrices;
                })
                ->sortBy("base_price");

            $final_charging_approvers = $fixed_charging_approvers->concat(
                $price_based_charging_approvers
            );

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
                "one_charging_id" => $request->one_charging_id,
                "one_charging_sync_id" => $request->one_charging_sync_id,
                "one_charging_code" => $request->one_charging_code,
                "one_charging_name" => $request->one_charging_name,
                "business_unit_id" => $request->business_unit_id,
                "business_unit_code" => $request->business_unit_code,
                "business_unit_name" => $request->business_unit_name,
                "company_id" => $request->company_id,
                "company_code" => $request->company_code,
                "company_name" => $request->company_name,
                "department_id" => $request->department_id,
                "department_code" => $request->department_code,
                "department_name" => $request->department_name,
                "department_unit_id" => $request->department_unit_id,
                "department_unit_code" => $request->department_unit_code,
                "department_unit_name" => $request->department_unit_name,
                "location_id" => $request->location_id,
                "location_code" => $request->location_code,
                "location_name" => $request->location_name,
                "sub_unit_id" => $request->sub_unit_id,
                "sub_unit_code" => $request->sub_unit_code,
                "sub_unit_name" => $request->sub_unit_name,
                "account_title_id" => $request->account_title_id,
                "account_title_name" => $request->account_title_name,
                "asset" => $request->asset,
                "description" => $request->description,
                "ship_to_id" => $request->ship_to_id,
                "ship_to_name" => $request->ship_to_name,
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
                    "supplier_id" => $request->supplier_id,
                    "supplier_name" => $request->supplier_name,
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
                        $filename = "{$filenameOnly}_jr_id_{$job_order_request->id}_item_{$index}_file_{$fileIndex}.{$extension}";
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

            foreach ($final_charging_approvers as $approver) {
                JobHistory::create([
                    "jo_id" => $job_order_request->id,
                    "approver_type" => "charging",
                    "approver_id" => $approver->approver_id,
                    "approver_name" => $approver->approver_name,
                    "layer" => $layer_count++,
                ]);
            }

            if ($pr_draft) {
                $draft = JrDrafts::find($pr_draft);

                $draft->update([
                    "status" => "Submitted",
                ]);
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

            $message =
                isset($job_order_po) && $job_order_po
                    ? Message::PURCHASE_REQUEST_AND_ORDER_SAVE
                    : Message::JOB_REQUEST_SAVE;

            return GlobalFunction::save($message, [
                "pr" => new JobOrderResource($job_order_request),
                "po" =>
                    isset($job_order_po) && $job_order_po
                        ? new JoPoResource($job_order_po)
                        : null,
            ]);
        }
    }

    public function update(UpdateRequest $request, $id)
    {
        $user_id = Auth()->user()->id;
        $requestor_department_id = Auth()->user()->department_id;
        $requestor_department_unit_id = Auth()->user()->department_unit_id;
        $requestor_company_id = Auth()->user()->company_id;
        $requestor_business_id = Auth()->user()->business_unit_id;
        $requestor_location_id = Auth()->user()->location_id;
        $requestor_sub_unit_id = Auth()->user()->sub_unit_id;
        $requestor_one_charging_sync_id = Auth()->user()->one_charging_sync_id;

        $dateToday = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");
        $orders = $request->order;

        $current_jr = JobOrderTransaction::where("id", $id)->first();
        $current_po = JOPOTransaction::withoutTrashed()
            ->where("jo_number", $current_jr->jo_number)
            ->first();

        $newTotalPrice = array_sum(array_column($orders, "total_price"));

        $currentItems = JobItems::where("jo_transaction_id", $id)->get();

        $currentTotalPrice = $currentItems->sum("total_price");

        $amount_min_max = JobOrderMinMax::first();

        if (is_null($amount_min_max)) {
            return GloblaFunction::notFound(Message::NO_MIN_MAX);
        }

        $is_price_increased = $newTotalPrice > $currentTotalPrice;
        $is_price_decreased = $newTotalPrice < $currentTotalPrice;

        if ($request->outside_labor == "pms") {
            $was_direct = $currentTotalPrice <= $amount_min_max->amount_min;

            $will_be_direct = $newTotalPrice <= $amount_min_max->amount_min;
        } else {
            $was_direct = $current_jr->outside_labor;
            if (!$was_direct) {
                $was_direct = $currentTotalPrice <= $amount_min_max->amount_min;
            }

            $will_be_direct = $request->outside_labor;
            if (!$will_be_direct) {
                $will_be_direct = $newTotalPrice <= $amount_min_max->amount_min;
            }

            if (
                !$request->outside_labor &&
                $newTotalPrice > $amount_min_max->amount_min
            ) {
                $will_be_direct = false;
            }
        }

        $common_data = [
            "outside_labor" => $request->outside_labor,
            "cap_ex" => $request->cap_ex,
            "date_needed" => $request->date_needed,
            "user_id" => $user_id,
            "type_id" => $request->type_id,
            "type_name" => $request->type_name,
            "one_charging_id" => $request->one_charging_id,
            "one_charging_sync_id" => $request->one_charging_sync_id,
            "one_charging_code" => $request->one_charging_code,
            "one_charging_name" => $request->one_charging_name,
            "business_unit_id" => $request->business_unit_id,
            "business_unit_code" => $request->business_unit_code,
            "business_unit_name" => $request->business_unit_name,
            "company_id" => $request->company_id,
            "company_code" => $request->company_code,
            "company_name" => $request->company_name,
            "department_id" => $request->department_id,
            "department_code" => $request->department_code,
            "department_name" => $request->department_name,
            "department_unit_id" => $request->department_unit_id,
            "department_unit_code" => $request->department_unit_code,
            "department_unit_name" => $request->department_unit_name,
            "location_id" => $request->location_id,
            "location_code" => $request->location_code,
            "location_name" => $request->location_name,
            "sub_unit_id" => $request->sub_unit_id,
            "sub_unit_code" => $request->sub_unit_code,
            "sub_unit_name" => $request->sub_unit_name,
            "account_title_id" => $request->account_title_id,
            "account_title_name" => $request->account_title_name,
            "asset" => $request->asset,
            "description" => $request->description,
            "ship_to_id" => $request->ship_to_id,
            "ship_to_name" => $request->ship_to_name,
        ];

        $current_jr->update(
            array_merge($common_data, [
                "jo_description" => $request->jo_description,
                "supplier_id" => $request->supplier_id,
                "supplier_name" => $request->supplier_name,
            ])
        );

        if ($was_direct) {
            if (!$will_be_direct) {
                $charging_setting_id = GlobalFunction::job_request_charger_setting_id(
                    $request->one_charging_sync_id
                );

                if (!$charging_setting_id) {
                    return GlobalFunction::invalid(
                        Message::NO_APPROVERS_SETTINGS_YET
                    );
                }

                $charging_approvers = JobOrderApprovers::where(
                    "job_order_id",
                    $charging_setting_id->id
                )
                    ->latest()
                    ->get();

                if (!$charging_approvers) {
                    return GlobalFunction::invalid(
                        Message::NO_APPROVERS_SETTINGS_YET
                    );
                }

                $current_jr->update([
                    "status" => "Pending",
                    "approved_at" => null,
                    "direct_po" => null,
                    "cancelled_at" => null,
                    "rejected_at" => null,
                ]);

                if ($current_po) {
                    JoPoOrders::where("jo_po_id", $current_po->id)->delete();

                    JoPoHistory::where("jo_po_id", $current_po->id)->delete();

                    $current_po->delete();
                }

                $fixed_charging_approvers = $charging_approvers->take(2);

                $price_based_charging_approvers = $charging_approvers
                    ->slice(2)
                    ->filter(function ($approver) use ($newTotalPrice) {
                        return $approver->base_price <= $newTotalPrice;
                    })
                    ->sortBy("base_price");

                $final_charging_approvers = $fixed_charging_approvers->concat(
                    $price_based_charging_approvers
                );

                $layer_count = 1;
                JobHistory::where("jo_id", $current_jr->id)->delete();

                foreach ($final_charging_approvers as $approver) {
                    JobHistory::create([
                        "jo_id" => $current_jr->id,
                        "approver_type" => "charging",
                        "approver_id" => $approver->approver_id,
                        "approver_name" => $approver->approver_name,
                        "layer" => $layer_count++,
                    ]);
                }
            } else {
                if ($is_price_increased) {
                    $current_jr->update([
                        "status" => "Approved",
                        "direct_po" => $dateToday,
                        "cancelled_at" => null,
                        "rejected_at" => null,
                    ]);

                    if ($current_po) {
                        $current_po->update([
                            "supplier_id" => $request->supplier_id,
                            "supplier_name" => $request->supplier_name,
                            "status" => "Pending",
                            "cancelled_at" => null,
                            "rejected_at" => null,
                            "direct_po" => $dateToday,
                            "layer" => "1",
                        ]);

                        JoPoHistory::where(
                            "jo_po_id",
                            $current_po->id
                        )->forceDelete();

                        $requestor_setting_id = GlobalFunction::job_request_requestor_setting_id(
                            $requestor_one_charging_sync_id
                        );

                        if (!$requestor_setting_id) {
                            return GlobalFunction::invalid(
                                Message::NO_APPROVERS_SETTINGS_YET
                            );
                        }

                        $charging_setting_id = GlobalFunction::job_request_charger_setting_id(
                            $request->one_charging_sync_id
                        );

                        if (!$charging_setting_id) {
                            return GlobalFunction::invalid(
                                Message::NO_APPROVERS_SETTINGS_YET
                            );
                        }

                        $requestor_approvers = JobOrderApprovers::where(
                            "job_order_id",
                            $requestor_setting_id->id
                        )
                            ->where("layer", "1")
                            ->get();

                        $final_requestor_approvers = $requestor_approvers->take(
                            1
                        );

                        if (
                            $requestor_setting_id->id !==
                            $charging_setting_id->id
                        ) {
                            $charging_approvers = JobOrderApprovers::where(
                                "job_order_id",
                                $charging_setting_id->id
                            )
                                ->where("layer", "1")
                                ->get();

                            $final_charging_approvers = $charging_approvers->take(
                                1
                            );
                        }

                        $final_charging_approvers;

                        $layer_count = 1;

                        if (
                            $requestor_setting_id->id ===
                            $charging_setting_id->id
                        ) {
                            foreach ($final_requestor_approvers as $approver) {
                                JoPoHistory::create([
                                    "jo_po_id" => $current_po->id,
                                    "approver_type" => "service_provider",
                                    "approver_id" => $approver->approver_id,
                                    "approver_name" => $approver->approver_name,
                                    "layer" => $layer_count++,
                                ]);
                            }
                        } else {
                            foreach ($final_requestor_approvers as $approver) {
                                JoPoHistory::create([
                                    "jo_po_id" => $current_po->id,
                                    "approver_type" => "service_provider",
                                    "approver_id" => $approver->approver_id,
                                    "approver_name" => $approver->approver_name,
                                    "layer" => $layer_count++,
                                ]);
                            }

                            foreach ($final_charging_approvers as $approver) {
                                JoPoHistory::create([
                                    "jo_po_id" => $current_po->id,
                                    "approver_type" => "charging",
                                    "approver_id" => $approver->approver_id,
                                    "approver_name" => $approver->approver_name,
                                    "layer" => $layer_count++,
                                ]);
                            }
                        }
                    }
                } else {
                    $current_po->update(
                        array_merge($common_data, [
                            "supplier_id" => $request->supplier_id,
                            "supplier_name" => $request->supplier_name,
                            "jo_number" => $current_jr->jo_number,
                        ])
                    );
                }
            }
        } else {
            if ($will_be_direct) {
                $requestor_setting_id = GlobalFunction::job_request_requestor_setting_id(
                    $requestor_one_charging_sync_id
                );

                if (!$requestor_setting_id) {
                    return GlobalFunction::invalid(
                        Message::NO_APPROVERS_SETTINGS_YET
                    );
                }

                $charging_setting_id = GlobalFunction::job_request_charger_setting_id(
                    $request->one_charging_sync_id
                );

                if (!$charging_setting_id) {
                    return GlobalFunction::invalid(
                        Message::NO_APPROVERS_SETTINGS_YET
                    );
                }

                $current_jr->update([
                    "status" => "Approved",
                    "approved_at" => $dateToday,
                    "direct_po" => $dateToday,
                    "cancelled_at" => null,
                    "rejected_at" => null,
                ]);

                JobHistory::where("jo_id", $current_jr->id)->forceDelete();

                if (!$current_po) {
                    $current_year = Carbon::now()->format("Y");
                    $latest_po = GlobalFunction::latest_jo($current_year);

                    $new_po_number = $latest_po
                        ? (int) explode("-", $latest_po->po_year_number_id)[2] +
                            1
                        : 1;

                    $po_year_number_id =
                        $current_year .
                        "-JO-" .
                        str_pad($new_po_number, 3, "0", STR_PAD_LEFT);

                    $jo_number = JOPOTransaction::withTrashed()
                        ->latest()
                        ->first();

                    $latest_po_number =
                        JOPOTransaction::withTrashed()->max("po_number") ?? 0;
                    $po_increment = $latest_po_number + 1;

                    $current_po = JOPOTransaction::create(
                        array_merge($common_data, [
                            "po_year_number_id" => $po_year_number_id,
                            "po_number" => $po_increment,
                            "jo_number" => $current_jr->jo_number,
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
                            "direct_po" => $dateToday,
                        ])
                    );

                    foreach ($currentItems as $currentItem) {
                        $currentItem->update([
                            "po_at" => $dateToday,
                            "purchase_order_id" => $current_po->id,
                        ]);
                    }
                }

                $requestor_approvers = JobOrderApprovers::where(
                    "job_order_id",
                    $requestor_setting_id->id
                )
                    ->where("layer", "1")
                    ->get();

                $final_requestor_approvers = $requestor_approvers->take(1);

                if ($requestor_setting_id->id !== $charging_setting_id->id) {
                    $charging_approvers = JobOrderApprovers::where(
                        "job_order_id",
                        $charging_setting_id->id
                    )
                        ->where("layer", "1")
                        ->get();

                    $final_charging_approvers = $charging_approvers->take(1);
                }

                $final_charging_approvers;

                $layer_count = 1;

                if ($requestor_setting_id->id === $charging_setting_id->id) {
                    foreach ($final_requestor_approvers as $approver) {
                        JoPoHistory::create([
                            "jo_po_id" => $current_po->id,
                            "approver_type" => "service_provider",
                            "approver_id" => $approver->approver_id,
                            "approver_name" => $approver->approver_name,
                            "layer" => $layer_count++,
                        ]);
                    }
                } else {
                    foreach ($final_requestor_approvers as $approver) {
                        JoPoHistory::create([
                            "jo_po_id" => $current_po->id,
                            "approver_type" => "service_provider",
                            "approver_id" => $approver->approver_id,
                            "approver_name" => $approver->approver_name,
                            "layer" => $layer_count++,
                        ]);
                    }

                    foreach ($final_charging_approvers as $approver) {
                        JoPoHistory::create([
                            "jo_po_id" => $current_po->id,
                            "approver_type" => "charging",
                            "approver_id" => $approver->approver_id,
                            "approver_name" => $approver->approver_name,
                            "layer" => $layer_count++,
                        ]);
                    }
                }
            } else {
                if ($is_price_increased) {
                    $current_jr->update([
                        "status" => "Pending",
                        "approved_at" => null,
                        "direct_po" => null,
                        "cancelled_at" => null,
                        "rejected_at" => null,
                    ]);

                    $charging_setting_id = GlobalFunction::job_request_charger_setting_id(
                        $request->one_charging_sync_id
                    );

                    if (!$charging_setting_id) {
                        return GlobalFunction::invalid(
                            Message::NO_APPROVERS_SETTINGS_YET
                        );
                    }

                    $charging_approvers = JobOrderApprovers::where(
                        "job_order_id",
                        $charging_setting_id->id
                    )
                        ->latest()
                        ->get();

                    if (!$charging_approvers) {
                        return GlobalFunction::notFound(
                            Message::NO_APPROVERS_SETTINGS_YET
                        );
                    }

                    $fixed_charging_approvers = $charging_approvers->take(2);

                    $price_based_charging_approvers = $charging_approvers
                        ->slice(2)
                        ->filter(function ($approver) use ($newTotalPrice) {
                            return $approver->base_price <= $newTotalPrice;
                        })
                        ->sortBy("base_price");

                    $final_charging_approvers = $fixed_charging_approvers->concat(
                        $price_based_charging_approvers
                    );

                    $layer_count = 1;
                    JobHistory::where("jo_id", $current_jr->id)->forceDelete();

                    foreach ($final_charging_approvers as $approver) {
                        JobHistory::create([
                            "jo_id" => $current_jr->id,
                            "approver_type" => "charging",
                            "approver_id" => $approver->approver_id,
                            "approver_name" => $approver->approver_name,
                            "layer" => $layer_count++,
                        ]);
                    }
                } else {
                    $charging_setting_id = GlobalFunction::job_request_charger_setting_id(
                        $request->one_charging_sync_id
                    );

                    if (!$charging_setting_id) {
                        return GlobalFunction::invalid(
                            Message::NO_APPROVERS_SETTINGS_YET
                        );
                    }

                    $charging_approvers = JobOrderApprovers::where(
                        "job_order_id",
                        $charging_setting_id->id
                    )
                        ->latest()
                        ->get();

                    if (!$charging_approvers) {
                        return GlobalFunction::notFound(
                            Message::NO_APPROVERS_SETTINGS_YET
                        );
                    }

                    $fixed_charging_approvers = $charging_approvers->take(2);

                    $price_based_charging_approvers = $charging_approvers
                        ->slice(2)
                        ->filter(function ($approver) use ($newTotalPrice) {
                            return $approver->base_price <= $newTotalPrice;
                        })
                        ->sortBy("base_price");

                    $final_charging_approvers = $fixed_charging_approvers->concat(
                        $price_based_charging_approvers
                    );

                    $layer_count = 1;
                    JobHistory::where("jo_id", $current_jr->id)->forceDelete();

                    foreach ($final_charging_approvers as $approver) {
                        JobHistory::create([
                            "jo_id" => $current_jr->id,
                            "approver_type" => "charging",
                            "approver_id" => $approver->approver_id,
                            "approver_name" => $approver->approver_name,
                            "layer" => $layer_count++,
                        ]);
                    }
                }
            }
        }

        $existingItems = JobItems::withoutTrashed()
            ->where("jo_transaction_id", $id)
            ->get();

        if ($was_direct && !$will_be_direct) {
            JobItems::where("jo_transaction_id", $id)->update([
                "po_at" => null,
                "purchase_order_id" => null,
            ]);
        } elseif (!$was_direct && $will_be_direct) {
            JobItems::where("jo_transaction_id", $id)->update([
                "po_at" => $dateToday,
                "purchase_order_id" => $current_po ? $current_po->id : null,
            ]);
        }

        $resubmitted = $request->resubmit;

        if ($resubmitted) {
            if ($current_po) {
                $current_jr->update([
                    "status" => "Approved",
                    "approved_at" => $dateToday,
                    // "cancelled_at" => null,
                    "rejected_at" => null,
                ]);

                $current_po->update([
                    "layer" => 1,
                    "status" => "Pending",
                    // "cancelled_at" => null,
                    "rejected_at" => null,
                ]);
            } else {
                $current_jr->update([
                    "layer" => 1,
                    "status" => "Pending",
                    // "cancelled_at" => null,
                    "rejected_at" => null,
                ]);
            }
        }

        // Move $updatedItems initialization OUTSIDE the main loop
        $updatedItems = [];

        foreach ($orders as $index => $values) {
            // Check if item has ID (existing item) or not (new item)
            if (isset($values["id"])) {
                // EXISTING ITEM - Update it
                $existingItem = $existingItems->firstWhere("id", $values["id"]);
                if ($existingItem) {
                    // Track price changes BEFORE updating
                    $oldUnitPrice = $existingItem->unit_price;
                    $newUnitPrice = $values["unit_price"];
                    $oldTotalPrice = $existingItem->total_price;
                    $item_name = $existingItem->description;
                    $newTotalPrice =
                        $values["unit_price"] * $values["quantity"];

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

                    // Handle attachments for existing items
                    $attachments = $request["order"][$index]["attachment"];
                    if (!empty($attachments)) {
                        $filenames = [];
                        foreach ($attachments as $fileIndex => $file) {
                            $originalFilename = basename($file);
                            $info = pathinfo($originalFilename);
                            $filenameOnly = $info["filename"];
                            $extension = $info["extension"];
                            $filename = "{$filenameOnly}_jr_id_{$current_jr->id}_item_{$index}_file_{$fileIndex}.{$extension}";
                            $filenames[] = $filename;
                        }
                        $filenames = json_encode($filenames);
                    } else {
                        $filenames = $existingItem->attachment;
                    }

                    // Update existing item
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

                    if ($current_po && $will_be_direct) {
                        $jobItem = $existingItem;
                        JoPoOrders::updateOrCreate(
                            [
                                "jo_po_id" => $current_po->id,
                                "jo_transaction_id" => $current_jr->id,
                                "jo_item_id" => $jobItem->id,
                            ],
                            [
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
                            ]
                        );
                    }
                }
            } else {
                // NEW ITEM - Create it
                $attachments = $request["order"][$index]["attachment"];
                if (!empty($attachments)) {
                    $filenames = [];
                    foreach ($attachments as $fileIndex => $file) {
                        $originalFilename = basename($file);
                        $info = pathinfo($originalFilename);
                        $filenameOnly = $info["filename"];
                        $extension = $info["extension"];
                        $filename = "{$filenameOnly}_jr_id_{$current_jr->id}_item_{$index}_file_{$fileIndex}.{$extension}";
                        $filenames[] = $filename;
                    }
                    $filenames = json_encode($filenames);
                } else {
                    $filenames = null;
                }

                $jobItem = JobItems::create([
                    "jo_transaction_id" => $current_jr->id,
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
                    "po_at" => $will_be_direct ? $dateToday : null,
                    "purchase_order_id" =>
                        $will_be_direct && $current_po ? $current_po->id : null,
                ]);

                if ($current_po && $will_be_direct) {
                    $existingJoPoOrder = JoPoOrders::withTrashed()
                        ->where("jo_po_id", $current_po->id)
                        ->where("jo_transaction_id", $current_jr->id)
                        ->where("jo_item_id", $jobItem->id)
                        ->first();
                    if (!$existingJoPoOrder || $existingJoPoOrder->trashed()) {
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
            }
        }

        // MOVE ALL LOGGING OUTSIDE THE LOOP
        $activityDescription = $resubmitted
            ? "Job order purchase request ID: " .
                $id .
                " has been resubmitted by UID: " .
                $user_id
            : "Job order purchase request ID: {$current_jr->id} has been updated by UID: {$user_id}";

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

        LogHistory::create([
            "activity" => $activityDescription,
            "jo_id" => $current_jr->id,
            "action_by" => $user_id,
        ]);

        if ($current_po) {
            $activityDescription_po =
                "Job order purchase order ID: " .
                $current_po->id .
                " has been created by UID: " .
                $user_id;
            LogHistory::create([
                "activity" => $activityDescription_po,
                "jo_po_id" => $current_po->id,
                "action_by" => $user_id,
            ]);
        }

        return GlobalFunction::save(
            $current_po
                ? Message::JOB_REQUEST_AND_ORDER_UPDATE
                : Message::JOB_REQUEST_UPDATE,
            [
                "pr" => new JobOrderResource($current_jr),
                "po" => $current_po ? new JoPoResource($current_po) : null,
            ]
        );
    }

    public function destroy()
    {
    }

    public function cancel_jo(CancelRequest $request, $id)
    {
        $userId = auth()->id();
        $currentDateTime = Carbon::now()->timezone("Asia/Manila");

        $position = Auth()->user()->position_name;

        if ($position != "PURCHASING ASSOCIATE") {
            $transaction = JobOrderTransaction::find($id);

            if (!$transaction) {
                return GlobalFunction::notFound(Message::NOT_FOUND);
            }

            if ($transaction->status === "Cancelled") {
                return GlobalFunction::invalid(Message::CANCELLED_ALREADY);
            }

            // Update JR status
            $transaction->update([
                "status" => "Cancelled",
                "approved_at" => null,
                "cancelled_at" => $currentDateTime,
                "reason" => $request->reason,
            ]);

            $poTransaction = $transaction->jo_po_transaction->first();
            $logMessage = "";
            $responseMessage = "";

            if ($poTransaction) {
                // Cancel JR and JO
                $poTransaction->update([
                    "status" => "Cancelled",
                    "approved_at" => null,
                    "cancelled_at" => $currentDateTime,
                    "reason" => $request->reason,
                ]);
                if ($request->has("no_rr") && $request->no_rr === true) {
                    $logMessage = "Job Request and Job Order ID: JR - {$id}, JO - {$poTransaction->id} have been cancelled by UID: {$userId}. Reason: {$request->reason}";
                    $responseMessage = Message::JR_AND_JO_CANCELLED;
                } else {
                    // Cancel remaining items
                    $logMessage = "Job Request and Job Order ID: JR - {$id}, JO - {$poTransaction->id} have been cancelled for the remaining items by UID: {$userId}. Reason: {$request->reason}";
                    $responseMessage =
                        Message::JR_AND_JO_CANCELLED_REMAINING_ITEMS;
                }
            } else {
                // Cancel only JR
                $logMessage = "Job Request ID: {$id} has been cancelled by UID: {$userId}. Reason: {$request->reason}";
                $responseMessage = Message::JOB_REQUEST_CANCELLED;
            }

            // Log the activity
            LogHistory::create([
                "activity" => $logMessage,
                "jo_id" => $id,
                "jo_po_id" => $poTransaction->id ?? null,
                "action_by" => $userId,
            ]);

            return GlobalFunction::responseFunction(
                $responseMessage,
                $transaction
            );
        } else {
            $no_rr = $request->no_rr;
            $user = Auth()->user()->id;
            $po_cancel = JOPOTransaction::where("id", $id)
                ->with("jo_po_orders")
                ->get()
                ->first();

            if (!$po_cancel) {
                return GlobalFunction::notFound(Message::NOT_FOUND);
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
    }

    public function voided_jo(CancelRequest $request, $id)
    {
        $user = Auth()->user()->id;
        $dateToday = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $transaction = JobOrderTransaction::findOrFail($id);

        $po_transaction = JOPOTransaction::where(
            "jo_number",
            $transaction->id
        )->first();

        if ($transaction->status === "Voided") {
            return GlobalFunction::invalid(Message::VOIDED_ALREADY);
        }

        $transaction->update([
            "cancelled_at" => null,
            "voided_at" => $dateToday,
            "reason" => $request->reason,
            "status" => "Voided",
        ]);

        if ($po_transaction) {
            $po_transaction->update([
                "status" => "Voided",
                "voided_at" => $dateToday,
                "reason" => $request->reason,
            ]);
        }

        $activityDescription = $po_transaction
            ? "Job Order and Job Request ID: {$id} have been voided by UID: {$user}. Reason: {$request->reason}"
            : "Job Request ID: {$id} has been voided by UID: {$user}. Reason: {$request->reason}";

        LogHistory::create([
            "activity" => $activityDescription,
            "jo_id" => $id,
            "jo_po_id" => $po_transaction ? $po_transaction->id : null,
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

    public function update_job_request_outside_labor(StoreRequest $request, $id)
    {
        $user_id = Auth()->user()->id;
        $requestor_deptartment_id = Auth()->user()->department_id;
        $requestor_department_unit_id = Auth()->user()->department_unit_id;
        $requestor_company_id = Auth()->user()->company_id;
        $requestor_business_id = Auth()->user()->business_unit_id;
        $requestor_location_id = Auth()->user()->location_id;
        $requestor_sub_unit_id = Auth()->user()->sub_unit_id;
        $requestor_one_charging_sync_id = Auth()->user()->one_charging_sync_id;

        $orders = $request->order;
        $dateToday = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");
        $for_po_id = $request->boolean("for_po_only") ? $user_id : null;
        $date_today = $request->boolean("for_po_only") ? $dateToday : null;
        $rush = $request->boolean("rush") ? $dateToday : null;

        $job_order_request = JobOrderTransaction::where("id", $id)->first();

        $approvers_jr = $job_order_request->approver_history;

        if ($approvers_jr) {
            JobHistory::where("jo_id", $job_order_request->id)->forceDelete();
        }

        $job_order_po = JOPOTransaction::where(
            "jo_number",
            $job_order_request->jo_number
        )->first();

        if (!$job_order_request) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $requestor_setting_id = GlobalFunction::job_request_requestor_setting_id(
            $requestor_one_charging_sync_id
        );

        if (!$requestor_setting_id) {
            return GlobalFunction::invalid(Message::NO_APPROVERS_SETTINGS_YET);
        }

        $charging_setting_id = GlobalFunction::job_request_charger_setting_id(
            $request->one_charging_sync_id
        );

        if (!$charging_setting_id) {
            return GlobalFunction::invalid(Message::NO_APPROVERS_SETTINGS_YET);
        }

        $common_data = [
            "outside_labor" => $request->outside_labor,
            "cap_ex" => $request->cap_ex,
            "date_needed" => $request->date_needed,
            "user_id" => $user_id,
            "type_id" => $request->type_id,
            "type_name" => $request->type_name,
            "one_charging_id" => $request->one_charging_id,
            "one_charging_sync_id" => $request->one_charging_sync_id,
            "one_charging_code" => $request->one_charging_code,
            "one_charging_name" => $request->one_charging_name,
            "business_unit_id" => $request->business_unit_id,
            "business_unit_code" => $request->business_unit_code,
            "business_unit_name" => $request->business_unit_name,
            "company_id" => $request->company_id,
            "company_code" => $request->company_code,
            "company_name" => $request->company_name,
            "department_id" => $request->department_id,
            "department_code" => $request->department_code,
            "department_name" => $request->department_name,
            "department_unit_id" => $request->department_unit_id,
            "department_unit_code" => $request->department_unit_code,
            "department_unit_name" => $request->department_unit_name,
            "location_id" => $request->location_id,
            "location_code" => $request->location_code,
            "location_name" => $request->location_name,
            "sub_unit_id" => $request->sub_unit_id,
            "sub_unit_code" => $request->sub_unit_code,
            "sub_unit_name" => $request->sub_unit_name,
            "account_title_id" => $request->account_title_id,
            "account_title_name" => $request->account_title_name,
            "asset" => $request->asset,
            "description" => $request->description,
            "direct_po" => $dateToday,
            "ship_to_id" => $request->ship_to_id,
            "ship_to_name" => $request->ship_to_name,
        ];

        $job_order_request->update(
            array_merge($common_data, [
                "approved_at" => $dateToday,
                "jo_description" => $request->jo_description,
                "module_name" => "Job Order",
                "status" => "Approved",
                "rush" => $rush,
                "helpdesk_id" => $request->helpdesk_id,
                "for_po_only" => $date_today,
                "for_po_only_id" => $for_po_id,
                "updated_at" => $dateToday,
            ])
        );

        if ($request->resubmit === true) {
            $job_order_request->update(["rejected_at" => null]);
        }

        $job_items = [];
        $processed_attachments = [];

        foreach ($orders as $index => $values) {
            $attachments = $request["order"][$index]["attachment"];
            $filenames = null;

            if (!empty($attachments)) {
                $filenames = [];
                foreach ($attachments as $fileIndex => $file) {
                    $originalFilename = basename($file);
                    $info = pathinfo($originalFilename);
                    $filename = sprintf(
                        "%s_jr_id_%d_item_%d_file_%d.%s",
                        $info["filename"],
                        $job_order_request->id,
                        $index,
                        $fileIndex,
                        $info["extension"]
                    );
                    $filenames[] = $filename;
                }
                $filenames = json_encode($filenames);
            }

            $processed_attachments[$index] = $filenames;

            $job_item = JobItems::updateOrCreate(
                [
                    "jo_transaction_id" => $job_order_request->id,
                    "description" => $values["description"],
                    "asset_code" => $values["asset_code"],
                ],
                [
                    "uom_id" => $values["uom_id"],
                    "quantity" => $values["quantity"],
                    "unit_price" => $values["unit_price"],
                    "total_price" =>
                        $values["unit_price"] * $values["quantity"],
                    "remarks" => $values["remarks"],
                    "attachment" => $filenames,
                    "asset" => $values["asset"],
                    "helpdesk_id" => $values["helpdesk_id"],
                    "po_at" => $dateToday,
                ]
            );

            $job_items[] = $job_item;
        }

        $current_item_ids = collect($job_items)
            ->pluck("id")
            ->toArray();
        JobItems::where("jo_transaction_id", $id)
            ->whereNotIn("id", $current_item_ids)
            ->delete();

        if (!$job_order_po) {
            $current_year = Carbon::now()->format("Y");
            $latest_po = GlobalFunction::latest_jo($current_year);
            $new_po_number = $latest_po
                ? (int) explode("-", $latest_po->po_year_number_id)[2] + 1
                : 1;
            $po_year_number_id =
                $current_year .
                "-JO-" .
                str_pad($new_po_number, 3, "0", STR_PAD_LEFT);

            $latest_po_number =
                JOPOTransaction::withTrashed()->max("po_number") ?? 0;
            $po_increment = $latest_po_number + 1;

            $job_order_po = JOPOTransaction::create(
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
                    "rush" => $rush,
                    "layer" => "1",
                    "direct_po" => $dateToday,
                    "helpdesk_id" => $request->helpdesk_id,
                ])
            );

            foreach ($job_items as $item) {
                $item->update([
                    "purchase_order_id" => $job_order_po->id,
                ]);
            }

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
                    "attachment" => $processed_attachments[$index],
                    "remarks" => $values["remarks"],
                    "asset" => $values["asset"],
                    "asset_code" => $values["asset_code"],
                    "helpdesk_id" => $values["helpdesk_id"],
                ]);
            }
        } else {
            $job_order_po->update(
                array_merge($common_data, [
                    "po_description" => $request->jo_description,
                    "module_name" => $request->module_name,
                    "total_item_price" => $request->total_item_price,
                    "supplier_id" => $request->supplier_id,
                    "supplier_name" => $request->supplier_name,
                    "status" => "Pending",
                    "sgp" => $request->sgp,
                    "f1" => $request->f1,
                    "f2" => $request->f2,
                    "rush" => $rush,
                    "layer" => "1",
                    "direct_po" => $dateToday,
                    "helpdesk_id" => $request->helpdesk_id,
                    "updated_at" => $dateToday,
                ])
            );

            if ($request->resubmit === true) {
                $job_order_po->update(["cancelled_at" => null]);
            }

            foreach ($job_items as $item) {
                $item->update([
                    "purchase_order_id" => $job_order_po->id,
                ]);
            }

            foreach ($orders as $index => $values) {
                JoPoOrders::updateOrCreate(
                    [
                        "jo_po_id" => $job_order_po->id,
                        "jo_transaction_id" => $job_order_request->id,
                        "jo_item_id" => $job_items[$index]->id,
                    ],
                    [
                        "description" => $values["description"],
                        "uom_id" => $values["uom_id"],
                        "unit_price" => $values["unit_price"],
                        "quantity" => $values["quantity"],
                        "quantity_serve" => 0,
                        "total_price" =>
                            $values["unit_price"] * $values["quantity"],
                        "attachment" => $processed_attachments[$index],
                        "remarks" => $values["remarks"],
                        "asset" => $values["asset"],
                        "asset_code" => $values["asset_code"],
                        "helpdesk_id" => $values["helpdesk_id"],
                    ]
                );
            }

            JoPoOrders::where("jo_po_id", $job_order_po->id)
                ->whereNotIn("jo_item_id", $current_item_ids)
                ->delete();
        }

        JoPoHistory::where("jo_po_id", $job_order_po->id)->delete();

        $requestor_approvers = JobOrderApprovers::where(
            "job_order_id",
            $requestor_setting_id->id
        )
            ->where("layer", "1")
            ->get();

        $final_requestor_approvers = $requestor_approvers->take(1);

        $layer_count = 1;

        if ($requestor_setting_id->id === $charging_setting_id->id) {
            foreach ($final_requestor_approvers as $approver) {
                JoPoHistory::create([
                    "jo_po_id" => $job_order_po->id,
                    "approver_type" => "service provider",
                    "approver_id" => $approver->approver_id,
                    "approver_name" => $approver->approver_name,
                    "layer" => $layer_count++,
                ]);
            }
        } else {
            $charging_approvers = JobOrderApprovers::where(
                "job_order_id",
                $charging_setting_id->id
            )
                ->where("layer", "1")
                ->get();

            $final_charging_approvers = $charging_approvers->take(1);

            foreach ($final_requestor_approvers as $approver) {
                JoPoHistory::create([
                    "jo_po_id" => $job_order_po->id,
                    "approver_type" => "service provider",
                    "approver_id" => $approver->approver_id,
                    "approver_name" => $approver->approver_name,
                    "layer" => $layer_count++,
                ]);
            }

            foreach ($final_charging_approvers as $approver) {
                JoPoHistory::create([
                    "jo_po_id" => $job_order_po->id,
                    "approver_type" => "charging",
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
                " has been updated by UID: " .
                $user_id,
            "jo_id" => $job_order_request->id,
            "action_by" => $user_id,
        ]);

        LogHistory::create([
            "activity" =>
                ($job_order_po->wasRecentlyCreated
                    ? "New job order purchase order ID: "
                    : "Job order purchase order ID: ") .
                $job_order_po->id .
                " has been " .
                ($job_order_po->wasRecentlyCreated ? "created" : "updated") .
                " by UID: " .
                $user_id,
            "jo_po_id" => $job_order_po->id,
            "action_by" => $user_id,
        ]);

        return GlobalFunction::save(
            Message::PURCHASE_REQUEST_AND_ORDER_UPDATE,
            [
                "pr" => new JobOrderResource($job_order_request),
                "po" => new JoPoResource($job_order_po),
            ]
        );
    }

    public function buyer_jr(Request $request, $id)
    {
        $user_id = Auth()->user()->id;
        $job_request = JobOrderTransaction::with("order")->find($id);

        if ($job_request->cancelled_at) {
            return GlobalFunction::invalid(Message::CANCELLED_ALREADY);
        }

        $payload = $request->all();
        $is_update = $payload["updated"] ?? false;
        $payload_items = $payload["items"] ?? $payload;

        $item_ids = array_column($payload_items, "id");

        $pr_items = JobItems::whereIn("id", $item_ids)
            ->where("jo_transaction_id", $id)
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
            ? "Job request ID: $id - has been re-tagged to buyer for " .
                count($item_details) .
                " item(s). Details: " .
                $item_details_string
            : "Job request ID: $id -  has been tagged to buyer for " .
                count($item_details) .
                " item(s). Details: " .
                $item_details_string;

        LogHistory::create([
            "activity" => $activityDescription,
            "jo_id" => $id,
            "action_by" => $user_id,
        ]);

        return GlobalFunction::responseFunction(
            $is_update ? Message::BUYER_UPDATED : Message::BUYER_TAGGED,
            $pr_items
        );
    }
}
