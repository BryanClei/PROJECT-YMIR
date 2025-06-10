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
use App\Http\Requests\PADisplay;
use App\Functions\GlobalFunction;
use App\Models\JoPoOrdersReports;
use App\Models\PurchaseAssistant;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Http\Resources\PRPOResource;
use App\Http\Requests\ReportStatusRequest;
use App\Http\Resources\IntegrationGLResource;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\POItemsPurchasingAnalysisReports;

class ReportSummaryController extends Controller
{
    public function pr_summary(ReportStatusRequest $request)
    {
        $status = $request->status;
        $type = $request->type;

        $user_id = Auth()->user()->id;

        $pr_items = PRItemsReports::with(
            "uom",
            "transaction.vladimir_user",
            "transaction.regular_user",
            "transaction.approver_history",
            "transaction.po_transaction",
            "po_order.rr_orders.rr_transaction"
        )
            ->whereHas("transaction", function ($subQuery) use (
                $type,
                $user_id
            ) {
                $subQuery
                    ->when($type === "for_user", function ($q) use ($user_id) {
                        $q->where("user_id", $user_id);
                    })
                    ->when($type === "for_approver", function ($q) use (
                        $user_id
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

        $transform = function ($item) {
            $t = $item->transaction;
            if (!$t) {
                return $item;
            }

            $t->users =
                $t->module_name === "Asset" && $t->vladimir_user
                    ? [
                        "id" => $t->vladimir_user->id,
                        "employee_id" => $t->vladimir_user->employee_id,
                        "username" => $t->vladimir_user->username,
                        "first_name" => $t->vladimir_user->firstname,
                        "last_name" => $t->vladimir_user->lastname,
                    ]
                    : ($t->regular_user
                        ? [
                            "prefix_id" => $t->regular_user->prefix_id,
                            "id_number" => $t->regular_user->id_number,
                            "first_name" => $t->regular_user->first_name,
                            "middle_name" => $t->regular_user->middle_name,
                            "last_name" => $t->regular_user->last_name,
                            "mobile_no" => $t->regular_user->mobile_no,
                        ]
                        : []);

            unset($t->vladimir_user, $t->regular_user);
            return $item;
        };

        method_exists($pr_items, "getCollection")
            ? $pr_items->getCollection()->transform($transform)
            : $pr_items->transform($transform);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $pr_items
        );
    }

    public function po_summary(ReportStatusRequest $request)
    {
        $user = Auth()->user();

        $pr_items = POItemsReports::with([
            "uom",
            "po_transaction" => fn($q) => $q->withTrashed(),
            "po_transaction.approver_history",
            "po_transaction.vladimir_user",
            "po_transaction.regular_user",
            "po_transaction.pr_transaction",
            "rr_orders.rr_transaction",
        ])
            ->withTrashed()
            ->whereHas("po_transaction", function ($q) use ($request, $user) {
                $q->withTrashed()
                    ->when(
                        $request->type === "for_user",
                        fn($q) => $q->where("user_id", $user->id)
                    )
                    ->when(
                        $request->type === "for_approver",
                        fn($q) => $q->whereHas(
                            "approver_history",
                            fn($h) => $h
                                ->where("approver_id", $user->id)
                                ->whereNotNull("approved_at")
                        )
                    );
            })
            ->useFilters()
            ->dynamicPaginate();

        if ($pr_items->isEmpty()) {
            return GlobalFunction::notFound(Message::NO_DATA_FOUND);
        }

        $transform = function ($item) {
            $t = $item->po_transaction;
            if (!$t) {
                return $item;
            }

            $t->users =
                $t->module_name === "Asset" && $t->vladimir_user
                    ? [
                        "id" => $t->vladimir_user->id,
                        "employee_id" => $t->vladimir_user->employee_id,
                        "username" => $t->vladimir_user->username,
                        "first_name" => $t->vladimir_user->firstname,
                        "last_name" => $t->vladimir_user->lastname,
                    ]
                    : ($t->regular_user
                        ? [
                            "prefix_id" => $t->regular_user->prefix_id,
                            "id_number" => $t->regular_user->id_number,
                            "first_name" => $t->regular_user->first_name,
                            "middle_name" => $t->regular_user->middle_name,
                            "last_name" => $t->regular_user->last_name,
                            "mobile_no" => $t->regular_user->mobile_no,
                        ]
                        : []);

            unset($t->vladimir_user, $t->regular_user);
            return $item;
        };

        method_exists($pr_items, "getCollection")
            ? $pr_items->getCollection()->transform($transform)
            : $pr_items->transform($transform);

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

        $pr_items = JobItemsReports::with(
            "uom",
            "transaction.users",
            "transaction.approver_history",
            "transaction.jo_po_transaction",
            "jo_po_orders.rr_orders.jo_rr_transaction"
        )
            ->whereHas("transaction", function ($subQuery) use (
                $type,
                $user_id
            ) {
                $subQuery
                    ->when($type === "for_user", function ($q) use ($user_id) {
                        $q->where("user_id", $user_id);
                    })
                    ->when($type === "for_approver", function ($q) use (
                        $user_id
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

        $pr_items = JoPoOrdersReports::with([
            "uom",
            "jo_po_transaction" => function ($query) {
                $query->withTrashed();
            },
            "jo_po_transaction.users",
            "jo_po_transaction.jo_approver_history",
            "jo_po_transaction.jo_transaction",
            "rr_orders" => function ($query) {
                $query->withTrashed();
            },
            "rr_orders.jo_rr_transaction",
        ])
            ->withTrashed()
            ->whereHas("jo_po_transaction", function ($subQuery) use (
                $type,
                $user_id
            ) {
                $subQuery
                    ->when($type === "for_user", function ($q) use ($user_id) {
                        $q->where("user_id", $user_id);
                    })
                    ->when($type === "for_approver", function ($q) use (
                        $user_id
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
        $po_items = POItemsPurchasingAnalysisReports::with(
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

    public function purchase_monitoring(PADisplay $request)
    {
        $status = $request->status;
        $type = $request->type;

        $user_id = Auth()->user()->id;

        $pr_items = PRItemsReports::whereHas("transaction", function ($mQuery) {
            $mQuery->where("status", "Approved");
        })
            ->with([
                "uom",
                "transaction.vladimir_user",
                "transaction.regular_user",
                "transaction.approver_history",
            ])
            ->when($status == "to_po", function ($query) {
                $query
                    ->whereNull("buyer_id")
                    ->whereNull("buyer_name")
                    ->whereHas("transaction", function ($subQuery) {
                        $subQuery
                            ->whereNull("for_po_only")
                            ->whereNull("for_po_only_id");
                    });
            })
            ->when($status == "tagged_buyer", function ($query) {
                $query
                    ->whereNotNull("buyer_id")
                    ->whereNotNull("buyer_name")
                    ->whereHas("transaction", function ($subQuery) {
                        $subQuery
                            ->whereNull("for_po_only")
                            ->whereNull("for_po_only_id");
                    });
            })
            ->when($status == "for_po", function ($query) {
                $query->whereHas("transaction", function ($query) {
                    $query
                        ->whereNotNull("for_po_only")
                        ->whereNotNull("for_po_only_id");
                });
            })
            ->whereNull("po_at")
            ->dynamicPaginate();

        if ($pr_items->isEmpty()) {
            return GlobalFunction::notFound(Message::NO_DATA_FOUND);
        }

        $transform = function ($item) {
            $t = $item->transaction;
            if (!$t) {
                return $item;
            }

            $t->users =
                $t->module_name === "Asset" && $t->vladimir_user
                    ? [
                        "id" => $t->vladimir_user->id,
                        "employee_id" => $t->vladimir_user->employee_id,
                        "username" => $t->vladimir_user->username,
                        "first_name" => $t->vladimir_user->firstname,
                        "last_name" => $t->vladimir_user->lastname,
                    ]
                    : ($t->regular_user
                        ? [
                            "prefix_id" => $t->regular_user->prefix_id,
                            "id_number" => $t->regular_user->id_number,
                            "first_name" => $t->regular_user->first_name,
                            "middle_name" => $t->regular_user->middle_name,
                            "last_name" => $t->regular_user->last_name,
                            "mobile_no" => $t->regular_user->mobile_no,
                        ]
                        : []);

            unset($t->vladimir_user, $t->regular_user);
            return $item;
        };

        method_exists($pr_items, "getCollection")
            ? $pr_items->getCollection()->transform($transform)
            : $pr_items->transform($transform);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $pr_items
        );
    }

    public function pr_purchasing_reports(Request $request)
    {
        $status = $request->status;
        $type = $request->type;

        $user_id = Auth()->user()->id;

        $pr_items = PRItemsReports::with(
            "uom",
            "transaction.vladimir_user",
            "transaction.regular_user",
            "transaction.approver_history",
            "transaction.po_transaction.approver_history",
            "transaction.po_transaction.order",
            "transaction.po_transaction.order.rr_orders",
            "transaction.po_transaction.order.rr_orders.rr_transaction"
            // "po_order.rr_orders.rr_transaction"
        )
            ->whereHas("transaction", function ($subQuery) use (
                $type,
                $user_id
            ) {
                $subQuery
                    ->where("status", "Approved")
                    ->when($type === "for_user", function ($q) use ($user_id) {
                        $q->where("user_id", $user_id);
                    })
                    ->when($type === "for_approver", function ($q) use (
                        $user_id
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

        $transform = function ($item) {
            $t = $item->transaction;
            if (!$t) {
                return $item;
            }

            $t->users =
                $t->module_name === "Asset" && $t->vladimir_user
                    ? [
                        "id" => $t->vladimir_user->id,
                        "employee_id" => $t->vladimir_user->employee_id,
                        "username" => $t->vladimir_user->username,
                        "first_name" => $t->vladimir_user->firstname,
                        "last_name" => $t->vladimir_user->lastname,
                    ]
                    : ($t->regular_user
                        ? [
                            "prefix_id" => $t->regular_user->prefix_id,
                            "id_number" => $t->regular_user->id_number,
                            "first_name" => $t->regular_user->first_name,
                            "middle_name" => $t->regular_user->middle_name,
                            "last_name" => $t->regular_user->last_name,
                            "mobile_no" => $t->regular_user->mobile_no,
                        ]
                        : []);

            unset($t->vladimir_user, $t->regular_user);
            return $item;
        };

        method_exists($pr_items, "getCollection")
            ? $pr_items->getCollection()->transform($transform)
            : $pr_items->transform($transform);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $pr_items
        );
    }

    // public function pr_purchasing_reports()
    // {
    //     $pr_items = PRItemsReports::with(
    //         "uom",
    //         "transaction.vladimir_user",
    //         "transaction.regular_user",
    //         "transaction.approver_history",
    //         "transaction.po_transaction.order",
    //         "transaction.po_transaction.approver_history",
    //         "transaction.po_transaction.order.rr_orders",
    //         "transaction.po_transaction.order.rr_orders.rr_transaction",
    //     )
    //         ->orderByDesc("id")
    //         ->useFilters()
    //         ->get();

    //     if ($pr_items->isEmpty()) {
    //         return GlobalFunction::notFound(Message::NO_DATA_FOUND);
    //     }

    //     // Step 1: Flatten the items by po_order
    //     $flattenedItems = collect();

    //     foreach ($pr_items as $item) {
    //         $poTransactions = $item->transaction->po_transaction;

    //         if ($poTransactions->isNotEmpty()) {
    //             // Create separate item for each PO Transaction and its PO Orders
    //             foreach ($poTransactions as $poTransaction) {
    //                 // Convert to array
    //                 $itemArray = $item->toArray();

    //                 // Get the actual PO order items from the PO transaction
    //                 $itemArray['po_order'] = $poTransaction->order ? $poTransaction->order->toArray() : null;

    //                 // Keep only the current PO transaction in the transaction->po_transaction array
    //                 if (isset($itemArray['transaction']['po_transaction'])) {
    //                     $itemArray['transaction']['po_transaction'] = collect($itemArray['transaction']['po_transaction'])
    //                         ->where('id', $poTransaction->id)
    //                         ->values()
    //                         ->all();
    //                 }

    //                 // Convert back to object
    //                 $newItem = json_decode(json_encode($itemArray));
    //                 $flattenedItems->push($newItem);
    //             }
    //         } else {
    //             // No PO transactions
    //             $itemArray = $item->toArray();
    //             $itemArray['po_order'] = null;

    //             if (isset($itemArray['transaction']['po_transaction'])) {
    //                 $itemArray['transaction']['po_transaction'] = [];
    //             }

    //             $newItem = json_decode(json_encode($itemArray));
    //             $flattenedItems->push($newItem);
    //         }
    //     }

    //     // Step 2: Transform user data
    //     $transformed = $flattenedItems->map(function ($item) {
    //         $t = $item->transaction ?? null;

    //         if ($t) {
    //             $t->users = $t->module_name === "Asset" && isset($t->vladimir_user)
    //                 ? [
    //                     "id" => $t->vladimir_user->id ?? null,
    //                     "employee_id" => $t->vladimir_user->employee_id ?? null,
    //                     "username" => $t->vladimir_user->username ?? null,
    //                     "first_name" => $t->vladimir_user->firstname ?? null,
    //                     "last_name" => $t->vladimir_user->lastname ?? null,
    //                 ]
    //                 : (isset($t->regular_user)
    //                     ? [
    //                         "prefix_id" => $t->regular_user->prefix_id ?? null,
    //                         "id_number" => $t->regular_user->id_number ?? null,
    //                         "first_name" => $t->regular_user->first_name ?? null,
    //                         "middle_name" => $t->regular_user->middle_name ?? null,
    //                         "last_name" => $t->regular_user->last_name ?? null,
    //                         "mobile_no" => $t->regular_user->mobile_no ?? null,
    //                     ]
    //                     : []);

    //             // Remove the user objects to avoid duplication
    //             unset($t->vladimir_user, $t->regular_user);
    //         }

    //         return $item;
    //     });

    //     // Step 3: Manual pagination
    //     $page = request("page", 1);
    //     $perPage = request("per_page", 20);

    //     $paginated = new LengthAwarePaginator(
    //         $transformed->forPage($page, $perPage)->values(),
    //         $transformed->count(),
    //         $perPage,
    //         $page,
    //         ["path" => request()->url(), "query" => request()->query()]
    //     );

    //     return GlobalFunction::responseFunction(
    //         Message::PURCHASE_REQUEST_DISPLAY,
    //         $paginated
    //     );
    // }
}
