<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use App\Models\PrHistory;
use App\Response\Message;
use App\Models\JobHistory;
use App\Models\LogHistory;
use App\Models\JOApprovers;
use App\Models\SetApprover;
use Illuminate\Http\Request;
use App\Models\PRTransaction;
use App\Models\ApproverSettings;
use App\Functions\GlobalFunction;
use App\Models\PrApproverExpense;
use App\Models\JobOrderTransaction;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\JobOrderResource;
use App\Http\Requests\Approver\RejectRequest;
use App\Http\Resources\PRTransactionResource;

class PrApproverController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth()->user()->id;

        $status = $request->status;

        $user_id = User::where("id", $user)
            ->get()
            ->first();

        $pr_id = PrHistory::where("approver_id", $user)
            ->get()
            ->pluck("pr_id");
        $layer = PrHistory::where("approver_id", $user)
            ->get()
            ->pluck("layer");

        if (empty($pr_id) || empty($layer)) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $purchase_request = PrApproverExpense::with("order", "approver_history")
            ->where("module_name", "Inventoriables")
            ->useFilters()
            ->dynamicPaginate();

        if ($purchase_request->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        PRTransactionResource::collection($purchase_request);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $purchase_request
        );
    }

    public function expense(Request $request)
    {
        $user = Auth()->user()->id;

        $status = $request->status;

        $user_id = User::where("id", $user)
            ->get()
            ->first();

        $pr_id = PrHistory::where("approver_id", $user)
            ->get()
            ->pluck("pr_id");
        $layer = PrHistory::where("approver_id", $user)
            ->get()
            ->pluck("layer");

        if (empty($pr_id) || empty($layer)) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $purchase_request = PrApproverExpense::with("order", "approver_history")
            ->where("module_name", "Expense")
            ->useFilters()
            ->dynamicPaginate();

        if ($purchase_request->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        PRTransactionResource::collection($purchase_request);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $purchase_request
        );
    }

    public function assets_approver(Request $request)
    {
        $user = Auth()->user()->id;

        $status = $request->status;

        $user_id = User::where("id", $user)
            ->get()
            ->first();

        $pr_id = PrHistory::where("approver_id", $user)
            ->get()
            ->pluck("pr_id");
        $layer = PrHistory::where("approver_id", $user)
            ->get()
            ->pluck("layer");

        if (empty($pr_id) || empty($layer)) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $purchase_request = PrApproverExpense::with(
            "order",
            "approver_history",
            "log_history"
        )
            ->where("module_name", "Assets")
            ->useFilters()
            ->dynamicPaginate();

        if ($purchase_request->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        PRTransactionResource::collection($purchase_request);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $purchase_request
        );
    }

    public function job_order(Request $request)
    {
        $status = $request->status;

        $jo_approvers = JOApprovers::with(
            "order",
            "approver_history",
            "log_history"
        )
            ->useFilters()
            ->dynamicPaginate();

        if ($jo_approvers->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        JobOrderResource::collection($jo_approvers);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $jo_approvers
        );
    }

    public function approved(Request $request, $id)
    {
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $user = Auth()->user()->id;

        $user_id = User::where("id", $user)
            ->get()
            ->first();

        $set_approver = PrHistory::where("pr_id", $id)->get();

        if (empty($set_approver)) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $approved_history = PrHistory::where("pr_id", $id)
            ->where("approver_id", $user)
            ->get()
            ->first()
            ->update([
                "approved_at" => $date_today,
            ]);

        $activityDescription =
            "Purchase request ID: " .
            $id .
            " has been approved by UID: " .
            $user;

        LogHistory::create([
            "activity" => $activityDescription,
            "pr_id" => $id,
            "action_by" => $user,
        ]);

        $count = count($set_approver);

        $pr_transaction = PRTransaction::find($id);

        if ($count == $pr_transaction->layer) {
            $pr_transaction->update([
                "approved_at" => $date_today,
                "status" => "Approved",
            ]);
            $pr_collect = new PRTransactionResource($pr_transaction);
            return GlobalFunction::responseFunction(
                Message::APPORVED,
                $pr_collect
            );
        }
        $pr_transaction->update([
            "layer" => $pr_transaction->layer + 1,
            "status" => "For Approval",
        ]);

        $pr_collect = new PRTransactionResource($pr_transaction);
        return GlobalFunction::responseFunction(Message::APPORVED, $pr_collect);
    }

    public function cancelled(Request $request, $id)
    {
        $user = Auth()->user()->id;
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $pr_transaction = PRTransaction::find($id);

        // if ($pr_transaction->status == "For Approval") {
        //     return GlobalFunction::invalid(Message::INVALID_ACTION);
        // }

        $pr_transaction->update([
            "reason" => $request->reason,
            "rejected_at" => null,
            "cancelled_at" => $date_today,
            "status" => "Cancelled",
        ]);

        $activityDescription =
            "Purchase request ID: " .
            $id .
            " has been cancelled by UID: " .
            $user .
            " Reason : " .
            $request["reason"];

        LogHistory::create([
            "activity" => $activityDescription,
            "pr_id" => $id,
            "action_by" => $user,
        ]);

        $pr_collect = new PRTransactionResource($pr_transaction);

        return GlobalFunction::responseFunction(
            Message::CANCELLED,
            $pr_collect
        );
    }
    public function voided(Request $request, $id)
    {
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $pr_transaction = PRTransaction::find($id);

        $pr_transaction->update([
            "status" => "Voided",
            "voided_at" => $date_today,
        ]);

        $pr_collect = new PRTransactionResource($pr_transaction);
        return GlobalFunction::responseFunction(Message::VOIDED, $pr_collect);
    }
    public function rejected(RejectRequest $request, $id)
    {
        $user = Auth()->user()->id;
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $pr_transaction = PRTransaction::find($id);

        $pr_transaction->update([
            "status" => "Reject",
            "reason" => $request["reason"],
            "rejected_at" => $date_today,
        ]);

        $activityDescription =
            "Purchase request ID: " .
            $id .
            " has been rejected by UID: " .
            $user .
            " Reason : " .
            $request["reason"];

        LogHistory::create([
            "activity" => $activityDescription,
            "pr_id" => $id,
            "action_by" => $user,
        ]);

        $to_reject = PrHistory::where("pr_id", $pr_transaction->id)
            ->where("approver_id", $user)
            ->get()
            ->first()
            ->update([
                "rejected_at" => $date_today,
            ]);

        $pr_collect = new PRTransactionResource($pr_transaction);
        return GlobalFunction::responseFunction(Message::REJECTED, $pr_collect);
    }

    public function approved_jo(Request $request, $id)
    {
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $user = Auth()->user()->id;

        $user_id = User::where("id", $user)
            ->get()
            ->first();

        $set_approver = JobHistory::where("jo_id", $id)->get();

        if (empty($set_approver)) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $approved_history = JobHistory::where("jo_id", $id)
            ->where("approver_id", $user)
            ->get()
            ->first()
            ->update([
                "approved_at" => $date_today,
            ]);

        $activityDescription =
            "Job order purchase request ID: " .
            $id .
            " has been approved by UID: " .
            $user;

        LogHistory::create([
            "activity" => $activityDescription,
            "jo_id" => $id,
            "action_by" => $user,
        ]);

        $count = count($set_approver);

        $pr_transaction = JobOrderTransaction::find($id);

        if ($count == $pr_transaction->layer) {
            $pr_transaction->update([
                "approved_at" => $date_today,
                "status" => "Approved",
            ]);
            $pr_collect = new JobOrderResource($pr_transaction);
            return GlobalFunction::responseFunction(
                Message::APPORVED,
                $pr_collect
            );
        }

        $pr_transaction->update([
            "layer" => $pr_transaction->layer + 1,
            "status" => "For approval",
        ]);

        $pr_collect = new JobOrderResource($pr_transaction);
        return GlobalFunction::responseFunction(Message::APPORVED, $pr_collect);
    }

    public function cancelled_jo(Request $request, $id)
    {
        $user = Auth()->user()->id;
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $pr_transaction = JobOrderTransaction::find($id);

        $pr_transaction->update([
            "reason" => $request->reason,
            "cancelled_at" => $date_today,
            "status" => "Cancelled",
        ]);

        $activityDescription =
            "Job order purchase request ID: " .
            $id .
            " has been cancelled by UID: " .
            $user .
            " Reason : " .
            $request["reason"];

        LogHistory::create([
            "activity" => $activityDescription,
            "jo_id" => $id,
            "action_by" => $user,
        ]);

        $pr_collect = new JobOrderResource($pr_transaction);

        return GlobalFunction::responseFunction(
            Message::CANCELLED,
            $pr_collect
        );
    }
    public function voided_jo(Request $request, $id)
    {
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $pr_transaction = JobOrderTransaction::find($id);

        $pr_transaction->update([
            "status" => "Voided",
            "voided_at" => $date_today,
        ]);

        $pr_collect = new JobOrderResource($pr_transaction);
        return GlobalFunction::responseFunction(Message::VOIDED, $pr_collect);
    }
    public function rejected_jo(RejectRequest $request, $id)
    {
        $user = Auth()->user()->id;
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $pr_transaction = JobOrderTransaction::find($id);

        $pr_transaction->update([
            "status" => "Reject",
            "reason" => $request["reason"],
            "rejected_at" => $date_today,
        ]);

        $to_reject = JobHistory::where("jo_id", $pr_transaction->id)
            ->where("approver_id", $user)
            ->get()
            ->first()
            ->update([
                "rejected_at" => $date_today,
            ]);

        $activityDescription =
            "Job order purchase request ID: " .
            $id .
            " has been rejected by UID: " .
            $user .
            " Reason : " .
            $request["reason"];

        LogHistory::create([
            "activity" => $activityDescription,
            "jo_id" => $id,
            "action_by" => $user,
        ]);

        $pr_collect = new JobOrderResource($pr_transaction);
        return GlobalFunction::responseFunction(Message::REJECTED, $pr_collect);
    }

    public function pr_approver_badge()
    {
        $user = Auth()->user()->id;

        $pr_id = PrHistory::where("approver_id", $user)
            ->get()
            ->pluck("pr_id");
        $layer = PrHistory::where("approver_id", $user)
            ->get()
            ->pluck("layer");

        $Expense = PrApproverExpense::with("order", "approver_history")
            ->where("module_name", "Expense")
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
            ->count();

        $Inventoriables = PrApproverExpense::with("order", "approver_history")
            ->where("module_name", "Inventoriables")
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
            ->count();

        $Assets = PrApproverExpense::with("order", "approver_history")
            ->where("module_name", "Assets")
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
            ->count();

        $jo_id = JobHistory::where("approver_id", $user)
            ->get()
            ->pluck("jo_id");
        $layer = JobHistory::where("approver_id", $user)
            ->get()
            ->pluck("layer");

        $jo_approvers = JOApprovers::with(
            "order",
            "approver_history",
            "log_history"
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
            "expense_count" => $Expense,
            "inventoriables" => $Inventoriables,
            "assets" => $Assets,
            "job_orders" => $jo_approvers,
        ];

        return GlobalFunction::responseFunction(
            Message::DISPLAY_COUNT,
            $result
        );
    }
}
