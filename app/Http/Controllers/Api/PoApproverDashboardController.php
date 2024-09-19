<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use App\Models\PoHistory;
use App\Response\Message;
use App\Models\LogHistory;
use App\Models\JoPoHistory;
use Illuminate\Http\Request;
use App\Models\POTransaction;
use App\Models\JOPOTransaction;
use App\Http\Requests\JODisplay;
use App\Functions\GlobalFunction;
use App\Models\ApproverDashboard;
use App\Http\Requests\POADRequest;
use App\Http\Resources\PoResource;
use App\Http\Controllers\Controller;
use App\Http\Resources\JoPoResource;
use App\Models\ApproverDashboardJOPO;
use App\Http\Requests\Approver\RejectRequest;

class PoApproverDashboardController extends Controller
{
    public function index(POADRequest $request)
    {

        $user = Auth()->user()->id;

        $user_id = User::where("id", $user)
            ->get()
            ->first();

        $po_id = PoHistory::where("approver_id", $user)
            ->get()
            ->pluck("po_id");
        $layer = PoHistory::where("approver_id", $user)
            ->get()
            ->pluck("layer");

        if (empty($po_id) || empty($layer)) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $purchase_request = ApproverDashboard::with(
            "order",
            "order.category",
            "approver_history",
            "log_history.users",
            "pr_transaction",
            "pr_transaction.order"
        )
            ->orderByDesc("updated_at")
            ->useFilters()
            ->dynamicPaginate();

        if ($purchase_request->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        new PoResource($purchase_request);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $purchase_request
        );
    }

    public function view(Request $request, $id)
    {
        $user = Auth()->user()->id;

        $user_id = User::where("id", $user)
            ->get()
            ->first();

        $po_id = PoHistory::where("approver_id", $user)
            ->get()
            ->pluck("po_id");
        $layer = PoHistory::where("approver_id", $user)
            ->get()
            ->pluck("layer");

        if (empty($po_id) || empty($layer)) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $purchase_request = ApproverDashboard::with("order", "approver_history")
            ->where("id", $id)
            ->get()
            ->first();

        if (!$purchase_request) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        new PoResource($purchase_request);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $purchase_request
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

        $set_approver = PoHistory::where("po_id", $id)->get();

        if (empty($set_approver)) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $approved_history = PoHistory::where("po_id", $id)
            ->where("approver_id", $user)
            ->get()
            ->first()
            ->update([
                "approved_at" => $date_today,
            ]);

        $activityDescription =
            "Purchase order ID: " . $id . " has been approved by UID: " . $user;

        LogHistory::create([
            "activity" => $activityDescription,
            "po_id" => $id,
            "action_by" => $user,
        ]);

        $count = count($set_approver);

        $po_transaction = POTransaction::find($id);

        if ($count == $po_transaction->layer) {
            $po_transaction->update([
                "approved_at" => $date_today,
                "status" => "For Receiving",
            ]);
            $po_collect = new PoResource($po_transaction);
            return GlobalFunction::responseFunction(
                Message::APPROVED_PO,
                $po_collect
            );
        }

        $po_transaction->update([
            "layer" => $po_transaction->layer + 1,
            "status" => "For Approval",
        ]);

        $po_collect = new PoResource($po_transaction);
        return GlobalFunction::responseFunction(
            Message::APPROVED_PO,
            $po_collect
        );
    }

    public function cancel(Request $request, $id)
    {
        $user = Auth()->user()->id;
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $po_transaction = POTransaction::find($id)
            ->where("user_id", $user)
            ->get()
            ->first();

        if ($po_transaction->status == "For approval") {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $activityDescription =
            "Purchase order ID: " .
            $id .
            " has been cancelled by UID: " .
            $user .
            "Reason : " .
            $request->reason;

        LogHistory::create([
            "activity" => $activityDescription,
            "po_id" => $id,
            "action_by" => $user,
        ]);

        $po_transaction->update([
            "rejected_at" => null,
            "cancelled_at" => $date_today,
            "status" => "Cancelled",
            "reason" => $request["reason"],
        ]);

        $po_collect = new PoResource($po_transaction);

        return GlobalFunction::responseFunction(
            Message::CANCELLED,
            $po_collect
        );
    }
    public function voided(Request $request, $id)
    {
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $po_transaction = POTransaction::find($id);

        $po_transaction->update([
            "status" => "Voided",
            "voided_at" => $date_today,
        ]);

        $po_collect = new PoResource($po_transaction);
        return GlobalFunction::responseFunction(Message::VOIDED, $po_collect);
    }

    public function rejected(RejectRequest $request, $id)
    {
        $user = Auth()->user()->id;
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $po_transaction = POTransaction::find($id);

        if (!$po_transaction) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $po_transaction->update([
            "status" => "Reject",
            "reason" => $request["reason"],
            "rejected_at" => $date_today,
        ]);

        $activityDescription =
            "Purchase order ID: " .
            $id .
            " has been rejected by UID: " .
            $user .
            " Reason : " .
            $request["reason"];

        LogHistory::create([
            "activity" => $activityDescription,
            "po_id" => $id,
            "action_by" => $user,
        ]);

        $to_reject = PoHistory::where("po_id", $po_transaction->id)
            ->where("approver_id", $user)
            ->get()
            ->first()
            ->update([
                "rejected_at" => $date_today,
            ]);

        $po_collect = new PoResource($po_transaction);
        return GlobalFunction::responseFunction(Message::REJECTED, $po_collect);
    }

    public function approved_jo_po(Request $request, $id)
    {
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $user = Auth()->user()->id;

        $user_id = User::where("id", $user)
            ->get()
            ->first();

        $set_approver = JoPoHistory::where("jo_po_id", $id)->get();

        if (empty($set_approver)) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $approved_history = JoPoHistory::where("jo_po_id", $id)
            ->where("approver_id", $user)
            ->get()
            ->first()
            ->update([
                "approved_at" => $date_today,
            ]);

        $activityDescription =
            "Job order purchase order ID: " .
            $id .
            " has been approved by UID: " .
            $user;

        LogHistory::create([
            "activity" => $activityDescription,
            "jo_po_id" => $id,
            "action_by" => $user,
        ]);

        $count = count($set_approver);

        $jo_po_transaction = JOPOTransaction::find($id);

        if ($count == $jo_po_transaction->layer) {
            $jo_po_transaction->update([
                "approved_at" => $date_today,
                "status" => "For Receiving",
            ]);
            $po_collect = new JoPoResource($jo_po_transaction);
            return GlobalFunction::responseFunction(
                Message::APPORVED,
                $po_collect
            );
        }

        $jo_po_transaction->update([
            "layer" => $jo_po_transaction->layer + 1,
            "status" => "For Approval",
        ]);

        $jo_po_collect = new JoPoResource($jo_po_transaction);
        return GlobalFunction::responseFunction(
            Message::APPORVED,
            $jo_po_collect
        );
    }

    public function rejected_jo_po(RejectRequest $request, $id)
    {
        $user = Auth()->user()->id;
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $po_transaction = JOPOTransaction::find($id);

        if (!$po_transaction) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $po_transaction->update([
            "status" => "Reject",
            "reason" => $request["reason"],
            "rejected_at" => $date_today,
        ]);

        $activityDescription =
            "Job order purchase order ID: " .
            $id .
            " has been rejected by UID: " .
            $user .
            " Reason : " .
            $request["reason"];

        LogHistory::create([
            "activity" => $activityDescription,
            "jo_po_id" => $id,
            "action_by" => $user,
        ]);

        $to_reject = JoPoHistory::where("jo_po_id", $po_transaction->id)
            ->where("approver_id", $user)
            ->get()
            ->first()
            ->update([
                "rejected_at" => $date_today,
            ]);

        $po_collect = new JoPoResource($po_transaction);
        return GlobalFunction::responseFunction(Message::REJECTED, $po_collect);
    }

    public function approver_index_jo_po(JODisplay $request)
    {
        $user = Auth()->user()->id;

        $user_id = User::where("id", $user)
            ->get()
            ->first();

        $jo_po_id = JoPoHistory::where("approver_id", $user_id->id)
            ->get()
            ->pluck("jo_po_id");
        $layer = JoPoHistory::where("approver_id", $user_id)
            ->get()
            ->pluck("layer");

        if (empty($jo_po_id) || empty($layer)) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $purchase_request = ApproverDashboardJOPO::with(
            "jo_po_orders",
            "jo_approver_history"
        )
            ->orderByDesc("updated_at")
            ->useFilters()
            ->dynamicPaginate();

        if ($purchase_request->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        new JoPoResource($purchase_request);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $purchase_request
        );
    }

    public function approver_view_jo_po(Request $request, $id)
    {
        $user = Auth()->user()->id;

        $user_id = User::where("id", $user)
            ->get()
            ->first();

        $jo_po_id = JoPoHistory::where("approver_id", $user_id->id)
            ->get()
            ->pluck("jo_po_id");
        $layer = JoPoHistory::where("approver_id", $user_id)
            ->get()
            ->pluck("layer");

        if (empty($jo_po_id) || empty($layer)) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $purchase_request = JOPOTransaction::with(
            "jo_po_orders",
            "jo_approver_history"
        )
            ->where("id", $id)
            ->get()
            ->first();

        if (!$purchase_request) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        new JoPoResource($purchase_request);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $purchase_request
        );
    }
}
