<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Items;
use App\Models\POItems;
use App\Models\PRItems;
use App\Models\RROrders;
use App\Response\Message;
use App\Models\LogHistory;
use Illuminate\Http\Request;
use App\Models\POTransaction;
use App\Models\PRTransaction;
use App\Models\RRTransaction;
use App\Functions\GlobalFunction;
use App\Helpers\RRHelperFunctions;
use App\Http\Resources\PoResource;
use App\Http\Resources\RRResource;
use App\Http\Requests\PO\PORequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\PRViewRequest;
use App\Helpers\BadgeHelperFunctions;
use App\Http\Resources\RRSyncDisplay;
use App\Http\Resources\RROrdersResource;
use App\Http\Resources\PRTransactionResource;
use App\Http\Requests\AssetVladimir\UpdateRequest;
use App\Http\Requests\ReceivedReceipt\StoreRequest;
use App\Http\Resources\LogHistory\LogHistoryResource;

class RRTransactionController extends Controller
{
    public function index(Request $request)
    {
        $rr_transaction = RRTransaction::with([
            "rr_orders" => function ($query) {
                $query->withTrashed();
            },
            "log_history",
            "log_history.users",
            "pr_transaction.users",
            "pr_transaction",
        ])

            ->orderByDesc("updated_at")
            ->useFilters()
            ->dynamicPaginate();

        if ($rr_transaction->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        RRResource::collection($rr_transaction);

        return GlobalFunction::responseFunction(
            Message::RR_DISPLAY,
            $rr_transaction
        );
    }

    public function show($id)
    {
        $rr_transaction = RRTransaction::with(
            "pr_transaction.users",
            "pr_transaction.order",
            "rr_orders",
            "po_transaction",
            "po_order",
            "po_order.category"
        )
            ->where("id", $id)
            ->orderByDesc("updated_at")
            ->first();

        if (!$rr_transaction) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        new RRResource($rr_transaction);

        return GlobalFunction::responseFunction(
            Message::RR_DISPLAY,
            $rr_transaction
        );
    }

    public function store(StoreRequest $request)
    {
        $user_id = Auth()->user()->id;
        $po_transaction = POTransaction::findOrFail($request->po_no);

        $pr_id_exists = RRHelperFunctions::checkPRExists(
            $po_transaction,
            $request->pr_no
        );
        if (!$pr_id_exists) {
            return GlobalFunction::invalid(Message::NOT_FOUND);
        }

        $orders = $request->order;
        $po_items = RRHelperFunctions::getPoItems($orders);

        $validation_result = RRHelperFunctions::validateQuantities(
            $orders,
            $po_items
        );
        if ($validation_result !== true) {
            return $validation_result;
        }

        $rr_transaction = RRHelperFunctions::createRRTransaction(
            $po_transaction,
            $user_id,
            $request->tagging_id
        );

        $itemDetails = RRHelperFunctions::processOrders(
            $orders,
            $po_items,
            $rr_transaction
        );

        RRHelperFunctions::createLogHistory(
            $rr_transaction,
            $user_id,
            $itemDetails
        );

        $rr_collect = new RRResource($rr_transaction);

        return GlobalFunction::responseFunction(Message::RR_SAVE, $rr_collect);
    }

    public function update(Request $request, $id)
    {
        $rr_number = $id;
        $orders = $request->order;
        $rr_transaction = RRHelperFunctions::checkRRExists($rr_number);

        if (!$rr_transaction) {
            return GlobalFunction::invalid(Message::NOT_FOUND);
        }
        $itemIds = [];
        $rr_collect = [];
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        RRHelperFunctions::validateQuantityReceiving($orders, $request);

        foreach ($orders as $index => $values) {
            $rr_orders_id = $request["order"][$index]["item_id"];

            $po_orders = POItems::where("id", $rr_orders_id)
                ->get()
                ->first();

            $add_previous = RRHelperFunctions::createRROrderAndUpdatePOItem(
                $rr_orders_id,
                $request,
                $po_orders,
                $index,
                $rr_number,
                $itemDetails
            );

            $rr_collect[] = new RROrdersResource($add_previous);
        }

        $user_id = auth()->id();

        RRHelperFunctions::logRRTransaction(
            $rr_transaction,
            $itemDetails,
            $user_id
        );

        return GlobalFunction::responseFunction(
            Message::RR_UPDATE,
            $rr_collect
        );
    }

    public function index_po_approved(PRViewRequest $request)
    {
        $status = $request->status;
        $po_approve = POTransaction::with(
            "order",
            "approver_history",
            "rr_transaction",
            "rr_transaction.rr_orders"
        )
            ->orderByDesc("updated_at")
            ->useFilters()
            ->dynamicPaginate();

        if ($po_approve->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        PoResource::collection($po_approve);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $po_approve
        );
    }

    public function view_po_approved($id)
    {
        $po_approve = POTransaction::with("order", "rr_transaction")
            ->where("id", $id)
            ->first();

        if (!$po_approve) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $po_collect = new PoResource($po_approve);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $po_collect
        );
    }

    public function asset_vladimir($id)
    {
        $purchase_request = POTransaction::with([
            "rr_transaction.rr_orders",
            "order",
        ])
            ->where("module_name", "Asset")
            ->whereHas("rr_transaction", function ($query) use ($id) {
                $query->where("id", $id);
            })
            ->orderByDesc("updated_at")
            ->useFilters()
            ->dynamicPaginate();

        $is_empty = $purchase_request->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::RR_DISPLAY,
            RRSyncDisplay::collection($purchase_request)
        );
    }

    public function asset_sync(UpdateRequest $request)
    {
        $request_orders = $request->rr_number;
        $updated_records = [];

        foreach ($request_orders as $rr_number) {
            $rr_orders = RROrders::where("rr_number", $rr_number)->get();

            foreach ($rr_orders as $order) {
                $order->sync = true;
                $order->save();

                $updated_records[] = [
                    "rr_number" => $order->rr_number,
                    "sync" => $order->sync,
                ];
            }
        }

        return GlobalFunction::save(Message::ASSET_UPDATE, $updated_records);
    }

    public function logs()
    {
        $log_history = LogHistory::useFilters()->dynamicPaginate();

        if ($log_history->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        LogHistoryResource::collection($log_history);

        return GlobalFunction::responseFunction(
            Message::DISPLAY_LOG_HISTORY,
            $log_history
        );
    }

    public function report_pr(PRViewRequest $request)
    {
        $purchase_order = PRTransaction::with(
            "users",
            "order",
            "approver_history"
        )
            ->useFilters()
            ->dynamicPaginate();

        $is_empty = $purchase_order->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $purchase_order
        );
    }

    public function report_po(Request $request)
    {
        $from = $request->input("from");
        $to = $request->input("to");
        $po_items = POItems::with(
            "uom",
            "pr_item",
            "po_transaction.users",
            "po_transaction.pr_transaction",
            "po_transaction",
            "po_transaction.rr_transaction",
            "po_transaction.rr_transaction.rr_orders"
        )
            ->useFilters()
            ->dynamicPaginate();

        $is_empty = $po_items->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $po_items
        );
    }

    public function report_rr()
    {
        $rr_orders = RROrders::with(
            "order",
            "order.uom",
            "rr_transaction",
            "rr_transaction.pr_transaction",
            "rr_transaction.pr_transaction.users",
            "rr_transaction.po_transaction.company",
            "rr_transaction.po_transaction.department",
            "rr_transaction.po_transaction.department_unit",
            "rr_transaction.po_transaction.sub_unit",
            "rr_transaction.po_transaction.location",
            "rr_transaction.po_transaction.account_title",
            "rr_transaction.po_transaction.account_title.account_type",
            "rr_transaction.po_transaction.account_title.account_group",
            "rr_transaction.po_transaction.account_title.account_sub_group",
            "rr_transaction.po_transaction.account_title.financial_statement"
        )
            ->useFilters()
            ->dynamicPaginate();

        $is_empty = $rr_orders->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $rr_orders
        );
    }

    public function cancel_rr(Request $request, $id)
    {
        $reason = $request->remarks;
        $vlad_user = $request->v_name;
        $rdf_id = $request->rdf_id;

        $user = $vlad_user
            ? $rdf_id . " (" . $vlad_user . ")"
            : ($user = Auth()->user()->id);

        $rr_transaction = RRTransaction::where("id", $id)
            ->with("rr_orders", "po_order")
            ->get()
            ->first();

        if (!$rr_transaction) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $po_orders = $rr_transaction->rr_orders->pluck("item_id")->toArray();

        $po_items = POItems::whereIn("id", $po_orders)->get();

        foreach ($rr_transaction->rr_orders as $rr_order) {
            $po_item = $po_items->where("id", $rr_order->item_id)->first();

            if ($po_item) {
                $po_item->quantity_serve -= $rr_order->quantity_receive;
                $po_item->save();
            }

            $rr_order->delete();
        }

        $activityDescription = "Received Receipt ID: {$rr_transaction->id} has been cancelled by UID: {$user}. Reason: {$reason}.";

        LogHistory::create([
            "activity" => $activityDescription,
            "rr_id" => $rr_transaction->id,
        ]);

        $cancelled_rr_transaction = $rr_transaction;

        $rr_transaction->delete();

        return GlobalFunction::responseFunction(
            Message::RR_CANCELLATION,
            $cancelled_rr_transaction
        );
    }

    public function rr_badge()
    {
        $user_id = Auth()->user()->id;

        $result = [
            "for_receiving" => BadgeHelperFunctions::forReceiving(),
            "for_receiving_user" => BadgeHelperFunctions::forReceivingUser(
                $user_id
            ),
            "for_receiving_job_order" => BadgeHelperFunctions::rrJobOrderCount(),
            "for_receiving_job_order_user" => BadgeHelperFunctions::rrJobOrderCountUser(
                $user_id
            ),
        ];

        return GlobalFunction::responseFunction(
            Message::DISPLAY_COUNT,
            $result
        );
    }
}
