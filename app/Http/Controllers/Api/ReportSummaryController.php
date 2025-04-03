<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\POItems;
use App\Models\PRItems;
use App\Models\JobItems;
use App\Models\RROrders;
use App\Response\Message;
use App\Models\JoPoOrders;
use Illuminate\Http\Request;
use App\Models\POItemsReports;
use App\Models\PRItemsReports;
use App\Models\JobItemsReports;
use App\Functions\GlobalFunction;
use App\Models\JoPoOrdersReports;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReportStatusRequest;
use App\Http\Resources\IntegrationGLResource;

class ReportSummaryController extends Controller
{
    public function pr_summary(ReportStatusRequest $request)
    {
        $status = $request->status;
        $type = $request->type;

        $user_id = Auth()->user()->id;
        $user_department_id = Auth()->user()->department_id;

        $pr_items = PRItemsReports::with(
            "uom",
            "transaction.users",
            "transaction.approver_history"
        )
            ->whereHas("transaction", function ($subQuery) use (
                $type,
                $user_id,
                $user_department_id
            ) {
                $subQuery
                    ->when($type === "for_user", function ($q) use (
                        $user_id,
                        $user_department_id
                    ) {
                        $q->where("user_id", $user_id)->where(
                            "department_id",
                            $user_department_id
                        );
                    })
                    ->when($type === "for_approver", function ($q) use (
                        $user_id,
                        $user_department_id
                    ) {
                        $q->whereHas("approver_history", function (
                            $qsubQuery
                        ) use ($user_id) {
                            $qsubQuery
                                ->where("approver_id", $user_id)
                                ->whereNotNull("approved_at");
                        });
                    });
            })
            ->useFilters()
            ->dynamicPaginate();

        if ($pr_items->isEmpty()) {
            return GlobalFunction::notFound(Message::NO_DATA_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $pr_items
        );
    }

    public function po_summary(ReportStatusRequest $request)
    {
        $status = $request->status;
        $type = $request->type;

        $user_id = Auth()->user()->id;
        $user_department_id = Auth()->user()->department_id;

        $pr_items = POItemsReports::with([
            "uom",
            "po_transaction" => function ($query) {
                $query->withTrashed();
            },
            "po_transaction.approver_history",
            "po_transaction.users",
            "po_transaction.pr_transaction",
            "rr_orders.rr_transaction",
        ])
            ->withTrashed()
            ->whereHas("po_transaction", function ($subQuery) use (
                $type,
                $user_id,
                $user_department_id
            ) {
                $subQuery
                    ->withTrashed()
                    ->when($type === "for_user", function ($q) use (
                        $user_id,
                        $user_department_id
                    ) {
                        $q->where("user_id", $user_id)->where(
                            "department_id",
                            $user_department_id
                        );
                    })
                    ->when($type === "for_approver", function ($q) use (
                        $user_id,
                        $user_department_id
                    ) {
                        $q->whereHas("approver_history", function (
                            $qsubQuery
                        ) use ($user_id) {
                            $qsubQuery
                                ->where("approver_id", $user_id)
                                ->whereNotNull("approved_at");
                        });
                    });
            })
            ->useFilters()
            ->dynamicPaginate();

        if ($pr_items->isEmpty()) {
            return GlobalFunction::notFound(Message::NO_DATA_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $pr_items
        );
    }

    public function jr_summary(ReportStatusRequest $request)
    {
        $status = $request->status;
        $type = $request->type;

        $user_id = Auth()->user()->id;
        $user_department_id = Auth()->user()->department_id;

        $pr_items = JobItemsReports::with(
            "uom",
            "transaction.users",
            "transaction.approver_history"
        )
            ->whereHas("transaction", function ($subQuery) use (
                $type,
                $user_id,
                $user_department_id
            ) {
                $subQuery
                    ->when($type === "for_user", function ($q) use (
                        $user_id,
                        $user_department_id
                    ) {
                        $q->where("user_id", $user_id)->where(
                            "department_id",
                            $user_department_id
                        );
                    })
                    ->when($type === "for_approver", function ($q) use (
                        $user_id,
                        $user_department_id
                    ) {
                        $q->whereHas("approver_history", function (
                            $qsubQuery
                        ) use ($user_id) {
                            $qsubQuery
                                ->where("approver_id", $user_id)
                                ->whereNotNull("approved_at");
                        });
                    });
            })
            ->useFilters()
            ->dynamicPaginate();

        if ($pr_items->isEmpty()) {
            return GlobalFunction::notFound(Message::NO_DATA_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $pr_items
        );
    }

    public function jo_summary(ReportStatusRequest $request)
    {
        $status = $request->status;
        $type = $request->type;

        $user_id = Auth()->user()->id;
        $user_department_id = Auth()->user()->department_id;

        $pr_items = JoPoOrdersReports::with([
            "uom",
            "jo_po_transaction" => function ($query) {
                $query->withTrashed();
            },
            "jo_po_transaction.users",
            "jo_po_transaction.jo_approver_history",
            "rr_orders" => function ($query) {
                $query->withTrashed();
            },
            "rr_orders.jo_rr_transaction",
        ])
            ->withTrashed()
            ->whereHas("jo_po_transaction", function ($subQuery) use (
                $type,
                $user_id,
                $user_department_id
            ) {
                $subQuery
                    ->when($type === "for_user", function ($q) use (
                        $user_id,
                        $user_department_id
                    ) {
                        $q->where("user_id", $user_id)->where(
                            "department_id",
                            $user_department_id
                        );
                    })
                    ->when($type === "for_approver", function ($q) use (
                        $user_id,
                        $user_department_id
                    ) {
                        $q->whereHas("jo_approver_history", function (
                            $qsubQuery
                        ) use ($user_id) {
                            $qsubQuery
                                ->where("approver_id", $user_id)
                                ->whereNotNull("approved_at");
                        });
                    });
            })
            ->useFilters()
            ->dynamicPaginate();

        if ($pr_items->isEmpty()) {
            return GlobalFunction::notFound(Message::NO_DATA_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $pr_items
        );
    }

    public function purchasing_analysis_summary()
    {
        $po_items = POItems::with(
            "uom",
            "supplier",
            "rr_orders",
            "po_transaction.users",
            "po_transaction.business_unit",
            "po_transaction.company",
            "po_transaction.department",
            "po_transaction.department_unit",
            "po_transaction.location",
            "po_transaction.sub_unit",
            "po_transaction.account_title",
            "po_transaction.approver_history",
            "po_transaction.pr_transaction.approver_history",
            "rr_orders.rr_transaction"
        )
            ->useFilters()
            ->dynamicPaginate();

        if ($po_items->isEmpty()) {
            return GlobalFunction::notFound(Message::NO_DATA_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $po_items
        );
    }

    public function purchasing_analysis_summary_jo()
    {
        $po_items = JoPoOrders::with(
            "uom",
            "jo_po_transaction.supplier",
            "rr_orders",
            "jo_po_transaction.users",
            "jo_po_transaction.business_unit",
            "jo_po_transaction.company",
            "jo_po_transaction.department",
            "jo_po_transaction.department_unit",
            "jo_po_transaction.location",
            "jo_po_transaction.sub_unit",
            "jo_po_transaction.account_title",
            "jo_po_transaction.approver_history",
            "jo_po_transaction.jo_transaction.approver_history",
            "rr_orders.jo_rr_transaction"
        )
            ->useFilters()
            ->dynamicPaginate();

        if ($po_items->isEmpty()) {
            return GlobalFunction::notFound(Message::NO_DATA_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::PURCHASE_ORDER_DISPLAY,
            $po_items
        );
    }

    public function stalwart_export(Request $request)
    {
        $adjustment_month = $request->adjustment_month;
        $date = Carbon::parse($adjustment_month);
        $from_date = $request->from;
        $to_date = $request->to;

        $month = $date->month;
        $year = $date->year;

        $stalwartQuery = RROrders::whereHas("rr_transaction", function (
            $query
        ) {
            $query->where("tagging_id", 2);
        })
            ->with([
                "rr_transaction.po_transaction" => function ($query) {
                    $query->withTrashed();
                },
            ])
            ->when($request->adjustment_month, function ($query) use (
                $request
            ) {
                $date = Carbon::parse($request->adjustment_month);
                return $query
                    ->whereYear("delivery_date", $date->year)
                    ->whereMonth("delivery_date", $date->month);
            })
            ->when($request->from, function ($query) use ($request) {
                $query->where("delivery_date", ">=", $request->from);
            })
            ->when($request->to, function ($query) use ($request) {
                $query->where("delivery_date", "<=", $request->to);
            })
            ->whereNull("deleted_at")
            ->dynamicPaginate();

        return IntegrationGLResource::collection($stalwartQuery);

        // Transform the data
        // return $collected = IntegrationGLResource::collection(
        //     $rr_transaction->items()
        // );

        // $result = array_merge(...$collected);

        // return $result;
    }
}
