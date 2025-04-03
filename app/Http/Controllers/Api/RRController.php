<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Items;
use App\Models\POItems;
use App\Models\PRItems;
use App\Models\JobItems;
use App\Response\Message;
use Illuminate\Http\Request;
use App\Models\POTransaction;
use App\Models\RRTransactionV2;
use App\Functions\GlobalFunction;
use App\Helpers\RRHelperFunctions;
use App\Http\Requests\RRDisplayV2;
use App\Models\AllowablePercentage;
use App\Http\Controllers\Controller;
use App\Http\Requests\PRViewRequest;
use App\Http\Resources\RRV2Resource;
use App\Http\Requests\RRTodayDisplay;
use App\Http\Resources\RRV2OrderResource;
use App\Http\Requests\ReceivedReceipt\StoreRequestV2;

class RRController extends Controller
{
    public function index(RRDisplayV2 $request)
    {
        $status = $request->status;

        $rr_transaction = RRTransactionV2::with([
            "rr_orders.po_transaction" => function ($query) {
                $query->withTrashed();
            },
            "rr_orders.pr_transaction",
            "rr_orders.order.uom",
            "log_history",
        ])
            ->orderByDesc("created_at")
            ->useFilters()
            ->dynamicPaginate();

        if ($rr_transaction->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        RRV2Resource::collection($rr_transaction);

        return GlobalFunction::responseFunction(
            Message::RR_DISPLAY,
            $rr_transaction
        );
    }

    public function rr_today(RRTodayDisplay $request)
    {
        $status = $request->status;
        $supplier = $request->supplier;
        $request_type = $request->request_type;
        $date_today = Carbon::now()->timeZone("Asia/Manila");

        $rr_transaction = RRTransactionV2::with("rr_orders")
            ->when($status === "rr_today", function ($query) use (
                $supplier,
                $request_type,
                $date_today
            ) {
                $query
                    ->whereHas("rr_orders.po_transaction", function (
                        $query
                    ) use ($supplier, $request_type, $date_today) {
                        $query
                            ->where("supplier_id", $supplier)
                            ->where("module_name", $request_type);
                    })
                    ->whereDate("created_at", $date_today);
            })
            ->useFilters()
            ->dynamicPaginate();

        if ($rr_transaction->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        RRV2Resource::collection($rr_transaction);

        return GlobalFunction::responseFunction(
            Message::RR_DISPLAY,
            $rr_transaction
        );
    }

    public function show($id)
    {
        $rr_transaction = RRTransactionV2::withTrashed()
            ->where("id", $id)
            ->with([
                "rr_orders" => function ($query) {
                    $query->withTrashed();
                },
                "rr_orders.po_transaction" => function ($query) {
                    $query->withTrashed();
                },
                "rr_orders.order" => function ($query) {
                    $query->withTrashed();
                },
                "rr_orders.order.uom",
                "log_history",
            ])
            ->useFilters()
            ->dynamicPaginate();

        if ($rr_transaction->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        new RRV2Resource($rr_transaction);

        return GlobalFunction::responseFunction(
            Message::RR_DISPLAY,
            $rr_transaction
        );
    }
    public function store(StoreRequestV2 $request)
    {
        $user_id = Auth()->user()->id;

        $po_numbers = array_unique(array_column($request->order, "po_no"));

        $po_transactions = POTransaction::whereIn(
            "po_number",
            $po_numbers
        )->get();

        foreach ($po_transactions as $po_transaction) {
            $pr_id_exists = RRHelperFunctions::checkPRExists(
                $po_transaction,
                $request->order[0]["pr_no"]
            );

            if (!$pr_id_exists) {
                return GlobalFunction::invalid(Message::NOT_FOUND);
            }
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
            $po_transactions->first(),
            $user_id,
            $request->tagging_id,
            $request->attahcment
        );

        $itemDetails = RRHelperFunctions::processOrders(
            $orders,
            $po_items,
            $rr_transaction,
            $po_transactions
        );

        RRHelperFunctions::createLogHistory(
            $rr_transaction,
            $user_id,
            $itemDetails
        );

        return GlobalFunction::responseFunction(
            Message::RR_SAVE,
            $rr_transaction
        );
    }

    public function update(Request $request, $id)
    {
        $user_id = auth()->id();
        $rr_number = $id;
        $rr_transaction = RRTransactionV2::where("id", $id)->first();
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        if (!$rr_transaction) {
            return GlobalFunction::invalid(Message::NOT_FOUND);
        }

        $po_numbers = array_unique(array_column($request->order, "po_no"));

        $po_transactions = POTransaction::whereIn(
            "po_number",
            $po_numbers
        )->get();

        foreach ($po_transactions as $po_transaction) {
            $pr_id_exists = RRHelperFunctions::checkPRExists(
                $po_transaction,
                $request->order[0]["pr_no"]
            );

            if (!$pr_id_exists) {
                return GlobalFunction::invalid(Message::NOT_FOUND);
            }
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

        foreach ($orders as $index => $value) {
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

            $rr_collect[] = new RRV2OrderResource($add_previous);
        }

        RRHelperFunctions::createLogHistory(
            $rr_transaction,
            $user_id,
            $itemDetails
        );

        return GlobalFunction::responseFunction(
            Message::RR_SAVE,
            $rr_transaction
        );
    }

    public function pr_admin_reports(PRViewRequest $request)
    {
        $pr_items = PRItems::with(
            "po_order.rr_orders.po_transaction",
            "po_order.rr_orders.rr_transaction",
            "uom",
            "transaction.users",
            "transaction.approver_history"
        )
            ->useFilters()
            ->dynamicPaginate();

        $is_empty = $pr_items->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $pr_items
        );
    }

    public function jr_admin_reports(PRViewRequest $request)
    {
        $status = $request->status;

        $jr_orders = JobItems::with(
            "jo_po_orders.jo_po_transaction",
            "jo_po_orders.rr_orders.jo_rr_transaction",
            "uom",
            "transaction.users"
        )
            ->orderBy("updated_at", "desc")
            ->useFilters()
            ->dynamicPaginate();

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $jr_orders
        );
    }
}
