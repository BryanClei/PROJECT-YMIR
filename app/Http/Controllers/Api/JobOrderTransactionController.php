<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use App\Models\JobItems;
use App\Models\JobOrder;
use App\Response\Message;
use App\Models\JobHistory;
use App\Models\LogHistory;
use App\Models\SetApprover;
use Illuminate\Http\Request;
use App\Models\ApproverSettings;
use App\Functions\GlobalFunction;
use App\Models\JobOrderApprovers;
use App\Models\JobOrderTransaction;
use App\Http\Controllers\Controller;
use App\Http\Requests\PRViewRequest;
use App\Http\Resources\JobOrderResource;
use App\Http\Requests\JobOrderTransaction\StoreRequest;

class JobOrderTransactionController extends Controller
{
    public function index(PRViewRequest $request)
    {
        $user_id = Auth()->user()->id;
        $status = $request->status;
        $job_order_request = JobOrderTransaction::with(
            "order",
            "approver_history",
            "log_history",
            "jo_po_transaction",
            "jo_po_transaction.jo_approver_history"
        )
            ->orderByDesc("updated_at")
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

    public function store(StoreRequest $request)
    {
        $user_id = Auth()->user()->id;

        if ($request->has("for_po_only")) {
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
        $latest_pr = JobOrderTransaction::where(
            "jo_year_number_id",
            "like",
            $current_year . "-J-%"
        )
            ->orderByRaw(
                "CAST(SUBSTRING_INDEX(jo_year_number_id, '-', -1) AS UNSIGNED) DESC"
            )
            ->first();

        if ($latest_pr) {
            $latest_number = explode("-", $latest_pr->jo_year_number_id)[2];
            $new_number = (int) $latest_number + 1;
        } else {
            $new_number = 1;
        }

        $latest_pr_number =
            JobOrderTransaction::withTrashed()->max("jo_number") ?? 0;
        $increment = $latest_pr_number + 1;

        $pr_year_number_id =
            $current_year . "-J-" . str_pad($new_number, 3, "0", STR_PAD_LEFT);

        $user_details = User::with(
            "company",
            "business_unit",
            "department",
            "department_unit",
            "sub_unit",
            "location"
        )
            ->where("id", $user_id)
            ->get()
            ->first();

        $job_order_request = new JobOrderTransaction([
            "jo_year_number_id" => $pr_year_number_id,
            "jo_number" => $increment,
            "jo_description" => $request["jo_description"],
            "date_needed" => $request["date_needed"],
            "user_id" => $user_id,
            "type_id" => $request["type_id"],
            "type_name" => $request["type_name"],
            "business_unit_id" => $request["business_unit_id"],
            "business_unit_name" => $request["business_unit_name"],
            "company_id" => $request["company_id"],
            "company_name" => $request["company_name"],
            "department_id" => $request["department_id"],
            "department_name" => $request["department_name"],
            "department_unit_id" => $request["department_unit_id"],
            "department_unit_name" => $request["department_unit_name"],
            "location_id" => $request["location_id"],
            "location_name" => $request["location_name"],
            "sub_unit_id" => $request["sub_unit_id"],
            "sub_unit_name" => $request["sub_unit_name"],
            "account_title_id" => $request["account_title_id"],
            "account_title_name" => $request["account_title_name"],
            "asset" => $request["asset"],
            "module_name" => "Job Order",
            "status" => "Pending",
            "layer" => "1",
            "description" => $request["description"],
            "helpdesk_id" => $request["helpdesk_id"],
        ]);

        $job_order_request->save();

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

            JobItems::create([
                "jo_transaction_id" => $job_order_request->id,
                "description" => $request["order"][$index]["description"],
                "uom_id" => $request["order"][$index]["uom_id"],
                "quantity" => $request["order"][$index]["quantity"],
                "unit_price" => $values["unit_price"],
                "total_price" => $values["unit_price"] * $values["quantity"],
                "remarks" => $request["order"][$index]["remarks"],
                "attachment" => $filenames,
                "assets" => $request["order"][$index]["asset"],
            ]);
        }
        $approver_settings = JobOrder::where("module", "Job Order")
            ->where("company_id", $job_order_request->company_id)

            ->where("business_unit_id", $job_order_request->business_unit_id)
            ->where("department_id", $job_order_request->department_id)
            ->where(
                "department_unit_id",
                $job_order_request->department_unit_id
            )
            ->where("sub_unit_id", $job_order_request->sub_unit_id)
            ->where("location_id", $job_order_request->location_id)
            ->whereHas("set_approver")
            ->get()
            ->first();

        $approvers = JobOrderApprovers::where(
            "job_order_id",
            $approver_settings->id
        )->get();

        foreach ($approvers as $index) {
            JobHistory::create([
                "jo_id" => $job_order_request->id,
                "approver_id" => $index["approver_id"],
                "approver_name" => $index["approver_name"],
                "layer" => $index["layer"],
            ]);
        }

        $activityDescription =
            "Job order purchase request ID: " .
            $job_order_request->id .
            " has been created by UID: " .
            $user_id;

        LogHistory::create([
            "activity" => $activityDescription,
            "jo_id" => $job_order_request->id,
            "action_by" => $user_id,
        ]);

        $pr_collect = new JobOrderResource($job_order_request);

        return GlobalFunction::save(
            Message::PURCHASE_REQUEST_SAVE,
            $pr_collect
        );
    }

    public function update(StoreRequest $request, $id)
    {
        $job_order_request = JobOrderTransaction::find($id);

        $not_found = JobOrderTransaction::where("id", $id)->exists();

        if (!$not_found) {
            return GlobalFunction::not_found(Message::NOT_FOUND);
        }
        $user_id = Auth()->user()->id;

        $orders = $request->order;

        $user_details = User::with(
            "company",
            "business_unit",
            "department",
            "department_unit",
            "sub_unit",
            "location"
        )
            ->where("id", $user_id)
            ->get()
            ->first();

        $job_order_request->update([
            "jo_number" => $job_order_request->id,
            "jo_description" => $request["jo_description"],
            "date_needed" => $request["date_needed"],
            "user_id" => $user_id,
            "type_id" => $request["type_id"],
            "type_name" => $request["type_name"],
            "business_unit_id" => $request["business_unit_id"],
            "business_unit_name" => $request["business_unit_name"],
            "company_id" => $request["company_id"],
            "company_name" => $request["company_name"],
            "department_id" => $request["department_id"],
            "department_name" => $request["department_name"],
            "department_unit_id" => $request["department_unit_id"],
            "department_unit_name" => $request["department_unit_name"],
            "location_id" => $request["location_id"],
            "location_name" => $request["location_name"],
            "sub_unit_id" => $request["sub_unit_id"],
            "sub_unit_name" => $request["sub_unit_name"],
            "account_title_id" => $request["account_title_id"],
            "account_title_name" => $request["account_title_name"],
            "asset" => $request["asset"],
            "module_name" => "Job Order",
            "description" => $request["description"],
            "helpdesk_id" => $request["helpdesk_id"],
        ]);

        $newOrders = collect($orders)
            ->pluck("id")
            ->toArray();
        $currentOrders = JobItems::where("jo_transaction_id", $id)
            ->get()
            ->pluck("id")
            ->toArray();

        foreach ($currentOrders as $order_id) {
            if (!in_array($order_id, $newOrders)) {
                JobItems::where("id", $order_id)->forceDelete();
            }
        }

        foreach ($orders as $index => $values) {
            JobItems::withTrashed()->updateOrCreate(
                [
                    "id" => $values["id"] ?? null,
                ],
                [
                    "jo_transaction_id" => $job_order_request->id,
                    "description" => $values["description"],
                    "uom_id" => $values["uom_id"],
                    "quantity" => $values["quantity"],
                    "unit_price" => $values["unit_price"],
                    "total_price" =>
                        $values["unit_price"] * $values["quantity"],
                    "remarks" => $values["remarks"],
                    "attachment" => $values["attachment"],
                    "asset" => $values["asset"],
                ]
            );
        }

        $activityDescription =
            "Job order purchase request ID: " .
            $id .
            " has been updated by UID: " .
            $user_id;

        LogHistory::create([
            "activity" => $activityDescription,
            "jo_id" => $id,
            "action_by" => $user_id,
        ]);

        $pr_collect = new JobOrderResource($job_order_request);

        return GlobalFunction::save(
            Message::PURCHASE_REQUEST_UPDATE,
            $pr_collect
        );
    }

    public function cancel_jo(Request $request, $id)
    {
        $user = Auth()->user()->id;
        $jo_cancel = JobOrderTransaction::where("id", $id)
            ->with("order")
            ->get()
            ->first();

        $jo_cancel->update([
            "reason" => $request->reason,
            "rejected_at" => null,
            "cancelled_at" => Carbon::now()
                ->timeZone("Asia/Manila")
                ->format("Y-m-d H:i"),
            "status" => "Cancelled",
        ]);

        $jo_cancel->order()->delete();
        $jo_cancel->delete();

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

        return GlobalFunction::responseFunction(Message::CANCELLED, $jo_cancel);
    }

    public function resubmit(Request $request, $id)
    {
        $job_order_request = JobOrderTransaction::find($id);
        $user_id = Auth()->user()->id;

        $not_found = JobOrderTransaction::where("id", $id)->exists();

        if (!$not_found) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $jo_history = JobHistory::where("jo_id", $id)->get();

        if ($jo_history->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        foreach ($jo_history as $jo) {
            $jo->update([
                "approved_at" => null,
                "rejected_at" => null,
            ]);
        }

        $orders = $request->order;

        $user_details = User::with(
            "company",
            "business_unit",
            "department",
            "department_unit",
            "sub_unit",
            "location"
        )
            ->where("id", $user_id)
            ->get()
            ->first();

        $job_order_request->update([
            "jo_number" => $job_order_request->id,
            "jo_description" => $request["jo_description"],
            "date_needed" => $request["date_needed"],
            "user_id" => $user_id,
            "type_id" => $request["type_id"],
            "type_name" => $request["type_name"],
            "business_unit_id" => $request["business_unit_id"],
            "business_unit_name" => $request["business_unit_name"],
            "company_id" => $request["company_id"],
            "company_name" => $request["company_name"],
            "department_id" => $request["department_id"],
            "department_name" => $request["department_name"],
            "department_unit_id" => $request["department_unit_id"],
            "department_unit_name" => $request["department_unit_name"],
            "location_id" => $request["location_id"],
            "location_name" => $request["location_name"],
            "sub_unit_id" => $request["sub_unit_id"],
            "sub_unit_name" => $request["sub_unit_name"],
            "account_title_id" => $request["account_title_id"],
            "account_title_name" => $request["account_title_name"],
            "asset" => $request["asset"],
            "module_name" => "Job Order",
            "description" => $request["description"],
            "helpdesk_id" => $request["helpdesk_id"],
            "status" => "Pending",
            "approved_at" => null,
            "rejected_at" => null,
            "voided_at" => null,
            "cancelled_at" => null,
        ]);

        $newOrders = collect($orders)
            ->pluck("id")
            ->toArray();
        $currentOrders = JobItems::where("jo_transaction_id", $id)
            ->get()
            ->pluck("id")
            ->toArray();

        foreach ($currentOrders as $order_id) {
            if (!in_array($order_id, $newOrders)) {
                JobItems::where("id", $order_id)->forceDelete();
            }
        }

        foreach ($orders as $index => $values) {
            JobItems::withTrashed()->updateOrCreate(
                [
                    "id" => $values["id"] ?? null,
                ],
                [
                    "jo_transaction_id" => $job_order_request->id,
                    "description" => $values["description"],
                    "uom_id" => $values["uom_id"],
                    "quantity" => $values["quantity"],
                    "unit_price" => $values["unit_price"],
                    "total_price" =>
                        $values["unit_price"] * $values["quantity"],
                    "remarks" => $values["remarks"],
                    "attachment" => $values["attachment"],
                    "asset" => $values["asset"],
                ]
            );
        }

        $activityDescription =
            "Job order purchase request ID: " .
            $id .
            " has been resubmitted by UID: " .
            $user_id;

        LogHistory::create([
            "activity" => $activityDescription,
            "jo_id" => $id,
            "action_by" => $user_id,
        ]);

        $jo_collect = new JobOrderResource($job_order_request);

        return GlobalFunction::save(Message::RESUBMITTED, $jo_collect);
    }

    public function destroy($id)
    {
        $job_order_request = JobOrderTransaction::where("id", $id)
            ->withTrashed()
            ->get();

        if ($job_order_request->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $job_order_request = JobOrderTransaction::withTrashed()->find($id);
        $is_active = JobOrderTransaction::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $job_order_request->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $job_order_request->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $job_order_request);
    }

    public function pa_jo_badge()
    {
        $for_po = JobOrderTransaction::with([
            "order" => function ($query) {
                $query->whereNull("po_at");
            },
        ])
            ->whereNull("cancelled_at")
            ->whereNull("voided_at")
            ->whereHas("approver_history", function ($query) {
                $query->whereNotNull("approved_at");
            })
            ->whereDoesntHave("jo_po_transaction")
            ->count();

        $pending = JobOrderTransaction::where("status", "Pending")
            ->whereNull("cancelled_at")
            ->whereNull("rejected_at")
            ->count();

        $approved_po = JobOrderTransaction::where("status", "Approved")
            ->whereNull("cancelled_at")
            ->whereNull("voided_at")
            ->whereHas("approver_history", function ($query) {
                $query->whereNotNull("approved_at");
            })
            ->with([
                "jo_po_transaction" => function ($query) {
                    $query->where("status", "Approved");
                },
                "jo_approver_history" => function ($query) {
                    $query->whereNotNull("approved_at");
                },
            ])
            ->count();

        $pending = JobOrderTransaction::whereNotNull("cancelled_at")
            ->whereNull("approved_at")
            ->count();

        $rejected = JobOrderTransaction::whereNotNull("rejected_at")->count();

        $result = [
            "for_po" => $for_po,
            "for_approval" => $pending,
            "approved_po" => $approved_po,
            "rejected_po" => $rejected,
        ];

        return GlobalFunction::responseFunction(
            Message::DISPLAY_COUNT,
            $result
        );
    }
}
