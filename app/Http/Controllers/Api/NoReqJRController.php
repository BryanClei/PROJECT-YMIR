<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Response\Message;
use Illuminate\Http\Request;
use App\Models\JobOrderMinMax;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\NoRequisitionJR\StoreRequest;

class NoReqJRController extends Controller
{
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

        if (is_null($amount_min_max)) {
            return GloblaFunction::notFound(Message::NO_MIN_MAX);
        }

        $charging_setting_id = JobOrder::where(
            "company_id",
            $request->company_id
        )
            ->where("business_unit_id", $request->business_unit_id)
            ->where("department_id", $request->department_id)
            ->where("department_unit_id", $request->department_unit_id)
            ->where("sub_unit_id", $request->sub_unit_id)
            ->where("location_id", $request->location_id)
            ->first();

        if (!$charging_setting_id) {
            return GlobalFunction::invalid(Message::NO_APPROVERS_SETTINGS_YET);
        }

        $charging_approvers = JobOrderApprovers::where(
            "job_order_id",
            $charging_setting_id->id
        )
            ->latest()
            ->get();

        if (!$charging_approvers) {
            return GlobalFunction::invalid(Message::NO_APPROVERS_SETTINGS_YET);
        }

        $fixed_charging_approvers = $charging_approvers->take(2);

        $charging_approvers = JobOrderApprovers::where(
            "job_order_id",
            $charging_setting_id->id
        )
            ->latest()
            ->get();

        $final_charging_approvers = $fixed_charging_approvers->concat(
            $price_based_charging_approvers
        );

        if (!$charging_approvers) {
            return GlobalFunction::invalid(Message::NO_APPROVERS_SETTINGS_YET);
        }

        $latest_jr = GlobalFunction::latest_jr($current_year);
        $new_number = $latest_jr
            ? (int) explode("-", $latest_jr->jo_year_number_id)[2] + 1
            : 1;

        $latest_jr_number =
            JobOrderTransaction::withTrashed()->max("jo_number") ?? 0;
        $increment = $latest_jr_number + 1;

        $pr_year_number_id =
            $current_year . "-JR-" . str_pad($new_number, 3, "0", STR_PAD_LEFT);

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
        ];

        $job_order_request = new JobOrderTransaction($common_data);

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
                "total_price" => $values["unit_price"] * $values["quantity"],
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

        LogHistory::create([
            "activity" =>
                "Job order purchase request ID: " .
                $job_order_request->id .
                " has been created by UID: " .
                $user_id,
            "jo_id" => $job_order_request->id,
            "action_by" => $user_id,
        ]);

        return GlobalFunction::save(Message::JOB_REQUEST_SAVE, [
            "pr" => new JobOrderResource($job_order_request),
        ]);
    }

    public function update(UpdateRequest $request, $id)
    {
        $user_id = Auth()->user()->id;
        $dateToday = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $job_order = JobOrderTransaction::findOrFail($id);

        $orders = $request->order;

        // Check if this is a resubmission
        $is_resubmitted =
            $request->has("is_resubmitted") && $request->is_resubmitted;

        $charging_setting_id = JobOrder::where(
            "company_id",
            $request->company_id
        )
            ->where("business_unit_id", $request->business_unit_id)
            ->where("department_id", $request->department_id)
            ->where("department_unit_id", $request->department_unit_id)
            ->where("sub_unit_id", $request->sub_unit_id)
            ->where("location_id", $request->location_id)
            ->first();

        if (!$charging_setting_id) {
            return GlobalFunction::invalid(Message::NO_APPROVERS_SETTINGS_YET);
        }

        $charging_approvers = JobOrderApprovers::where(
            "job_order_id",
            $charging_setting_id->id
        )
            ->latest()
            ->get();

        if (!$charging_approvers) {
            return GlobalFunction::invalid(Message::NO_APPROVERS_SETTINGS_YET);
        }

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
            "jo_description" => $request->jo_description,
            "rush" => $request->boolean("rush") ? $dateToday : null,
            "helpdesk_id" => $request->helpdesk_id,
            "layer" => "1",
        ];

        $job_order->update($common_data);

        foreach ($orders as $index => $values) {
            $attachments = $request["order"][$index]["attachment"];
            $filenames = null;

            if (!empty($attachments)) {
                $filenames = [];
                foreach ($attachments as $fileIndex => $file) {
                    if (isset($values["id"])) {
                        $filenames[] = $file;
                    } else {
                        $originalFilename = basename($file);
                        $info = pathinfo($originalFilename);
                        $filenameOnly = $info["filename"];
                        $extension = $info["extension"];
                        $filename = "{$filenameOnly}_jr_id_{$job_order->id}_item_{$index}_file_{$fileIndex}.{$extension}";
                        $filenames[] = $filename;
                    }
                }
                $filenames = json_encode($filenames);
            }

            JobItems::updateOrCreate(
                [
                    "id" => $values["id"] ?? null,
                    "jo_transaction_id" => $job_order->id,
                ],
                [
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
                ]
            );
        }

        $existing_item_ids = collect($orders)
            ->pluck("id")
            ->filter()
            ->toArray();
        JobItems::where("jo_transaction_id", $job_order->id)
            ->whereNotIn("id", $existing_item_ids)
            ->forceDelete();

        JobHistory::where("jo_id", $job_order->id)->delete();

        $layer_count = 1;
        foreach ($charging_approvers as $approver) {
            JobHistory::create([
                "jo_id" => $job_order->id,
                "approver_type" => "charging",
                "approver_id" => $approver->approver_id,
                "approver_name" => $approver->approver_name,
                "layer" => $layer_count++,
            ]);
        }

        $action_type = $is_resubmitted ? "resubmitted" : "updated";
        LogHistory::create([
            "activity" =>
                "Job order purchase request ID: " .
                $job_order->id .
                " has been {$action_type} by UID: " .
                $user_id,
            "jo_id" => $job_order->id,
            "action_by" => $user_id,
        ]);

        return GlobalFunction::update(Message::JOB_REQUEST_UPDATE, [
            "pr" => new JobOrderResource($job_order),
        ]);
    }
}
