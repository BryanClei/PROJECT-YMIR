<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use App\Models\LogHistory;
use Illuminate\Http\Request;
use App\Models\POTransaction;
use App\Models\RRTransaction;
use App\Models\JORRTransaction;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\FistoApi\TaggedRequest;

class FistoApiController extends Controller
{
    // Array Search
    public function index(Request $request)
    {
        $searchRRNumbers = $request->input("search", []);

        $search = is_string($searchRRNumbers)
            ? array_filter(explode(",", $searchRRNumbers))
            : $searchRRNumbers;

        $rr_orders = RRTransaction::with([
            "rr_orders.order" => function ($query) {
                $query->withTrashed();
            },
            "rr_orders.order.uom",
            "rr_orders.po_transaction" => function ($query) {
                $query->withTrashed();
            },
            "rr_orders.po_transaction.business_unit",
            "rr_orders.po_transaction.company",
            "rr_orders.po_transaction.department",
            "rr_orders.po_transaction.department_unit",
            "rr_orders.po_transaction.sub_unit",
            "rr_orders.po_transaction.location",
            "rr_orders.po_transaction.account_title",
            "rr_orders.po_transaction.account_title.credit",
            "rr_orders.po_transaction.order",
        ])
            ->where("tagging_id", 1)
            ->when($search, fn($q) => $q->whereIn("rr_year_number_id", $search))
            // ->when(!empty($search), function ($query) use ($search) {
            //     $query->where(function ($q) use ($search) {
            //         // foreach ($search as $rrNumber) {
            //         //     $q->orWhere(
            //         //         "rr_year_number_id",
            //         //         "like",
            //         //         "%{$rrNumber}%"
            //         //     );
            //         // }
            //         $q->whereIn("rr_year_number_id", "like", "%{$rrNumber}%");
            //     });
            // })
            ->dynamicPaginate();

        // Add type to RR transactions
        $rr_orders = $rr_orders->map(function ($item) {
            $data = $item->toArray();
            return array_merge(["type" => "RR"], $data);
        });

        $jo_rr_orders = JORRTransaction::with([
            "rr_orders.order" => function ($query) {
                $query->withTrashed();
            },
            "rr_orders.order.uom",
            "rr_orders.po_transaction" => function ($query) {
                $query->withTrashed();
            },
            "rr_orders.po_transaction",
            "rr_orders.po_transaction.business_unit",
            "rr_orders.po_transaction.company",
            "rr_orders.po_transaction.department",
            "rr_orders.po_transaction.department_unit",
            "rr_orders.po_transaction.sub_unit",
            "rr_orders.po_transaction.location",
            "rr_orders.po_transaction.account_title",
            "rr_orders.po_transaction.account_title.credit",
            "rr_orders.po_transaction.order",
        ])
            ->where("tagging_id", 1)
            ->when(
                $search,
                fn($q) => $q->whereIn("jo_rr_year_number_id", $search)
            )
            // ->when(!empty($search), function ($query) use ($search) {
            //     $query->where(function ($q) use ($search) {
            //         // foreach ($search as $rrNumber) {
            //         //     $q->orWhere(
            //         //         "jo_rr_year_number_id",
            //         //         "like",
            //         //         "%{$rrNumber}%"
            //         //     );
            //         // }
            //         $q->whereIn(
            //             "jo_rr_year_number_id",
            //             "like",
            //             "%{$rrNumber}%"
            //         );
            //     });
            // })
            ->dynamicPaginate();

        // Add type to JORR transactions
        $jo_rr_orders = $jo_rr_orders->map(function ($item) {
            $data = $item->toArray();
            return array_merge(["type" => "JORR"], $data);
        });

        $merged_orders = $rr_orders
            ->concat($jo_rr_orders)
            ->sortBy("created_at")
            ->values()
            ->toArray();

        // Check if the merged collection is empty
        if (empty($merged_orders)) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $merged_orders
        );
    }

    // Single Search only
    // public function index(Request $request)
    // {
    //     $search = $request->search;

    //     $rr_orders = RRTransaction::with([
    //         "rr_orders.order" => function ($query) {
    //             $query->withTrashed();
    //         },
    //         "rr_orders.order.uom",
    //         "rr_orders.po_transaction" => function ($query) {
    //             $query->withTrashed();
    //         },
    //         "rr_orders.po_transaction.business_unit",
    //         "rr_orders.po_transaction.company",
    //         "rr_orders.po_transaction.department",
    //         "rr_orders.po_transaction.department_unit",
    //         "rr_orders.po_transaction.sub_unit",
    //         "rr_orders.po_transaction.location",
    //         "rr_orders.po_transaction.account_title",
    //         "rr_orders.po_transaction.account_title.credit",
    //         "rr_orders.po_transaction.order",
    //     ])
    //         ->where("tagging_id", 1)
    //         ->when($search, function ($query) use ($search) {
    //             $query->where("rr_year_number_id", "like", "%{$search}%");
    //         })
    //         ->useFilters()
    //         ->dynamicPaginate();

    //     // Add type to RR transactions
    //     $rr_orders = $rr_orders->map(function ($item) {
    //         $data = $item->toArray();
    //         return array_merge(["type" => "RR"], $data);
    //     });

    //     $jo_rr_orders = JORRTransaction::with([
    //         "rr_orders.order" => function ($query) {
    //             $query->withTrashed();
    //         },
    //         "rr_orders.order.uom",
    //         "rr_orders.po_transaction" => function ($query) {
    //             $query->withTrashed();
    //         },
    //         "rr_orders.po_transaction",
    //         "rr_orders.po_transaction.business_unit",
    //         "rr_orders.po_transaction.company",
    //         "rr_orders.po_transaction.department",
    //         "rr_orders.po_transaction.department_unit",
    //         "rr_orders.po_transaction.sub_unit",
    //         "rr_orders.po_transaction.location",
    //         "rr_orders.po_transaction.account_title",
    //         "rr_orders.po_transaction.account_title.credit",
    //         "rr_orders.po_transaction.order",
    //     ])
    //         ->where("tagging_id", 1)
    //         ->when($search, function ($query) use ($search) {
    //             $query->where("jo_rr_year_number_id", "like", "%{$search}%");
    //         })
    //         ->useFilters()
    //         ->dynamicPaginate();

    //     // Add type to JORR transactions
    //     $jo_rr_orders = $jo_rr_orders->map(function ($item) {
    //         $data = $item->toArray();
    //         return array_merge(["type" => "JORR"], $data);
    //     });

    //     $merged_orders = $rr_orders
    //         ->concat($jo_rr_orders)
    //         ->sortBy("created_at")
    //         ->values()
    //         ->toArray();

    //     // Check if the merged collection is empty
    //     if (empty($merged_orders)) {
    //         return GlobalFunction::notFound(Message::NOT_FOUND);
    //     }

    //     return GlobalFunction::responseFunction(
    //         Message::PURCHASE_REQUEST_DISPLAY,
    //         $merged_orders
    //     );
    // }

    public function update(TaggedRequest $request, $id)
    {
        $fisto_id_no = $request->id_no;
        $voided_status = $request->voided;
        $status = $voided_status ? 0 : 1;

        $rr_transaction = RRTransaction::with(["rr_orders", "log_history"])
            ->where("id", $id)
            ->get()
            ->first();

        if (!$rr_transaction) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $rr_orders_to_tag = $rr_transaction->rr_orders->filter(function (
            $order
        ) {
            return $order->f_tagged === $status;
        });

        if ($rr_orders_to_tag->isEmpty()) {
            return GlobalFunction::notFound(Message::NO_ITEM_FOUND);
        }

        $message_status = $voided_status ? "voided" : "tagged";

        $taggedOrders = [];
        foreach ($rr_orders_to_tag as $rr_order) {
            $rr_order->update(["f_tagged" => $status]);
            $taggedOrders[] = $rr_order;
        }

        $taggedItems = $rr_orders_to_tag
            ->map(function ($order) {
                return "Item Name: " .
                    $order->item_name .
                    ", Quantity Received: " .
                    $order->quantity_receive .
                    ", Shipment No: " .
                    $order->shipment_no;
            })
            ->implode("; ");

        $activityDescription =
            "RR Transaction ID: " .
            $id .
            " has been " .
            $message_status .
            " by UID: " .
            $fisto_id_no .
            " from Fisto. " .
            $message_status .
            " Items: " .
            $taggedItems;

        LogHistory::create([
            "activity" => $activityDescription,
            "rr_id" => $id,
        ]);

        $rr_transaction->load([
            "rr_orders" => function ($query) use ($taggedOrders) {
                $query->whereIn("id", collect($taggedOrders)->pluck("id"));
            },
            "log_history" => function ($query) {
                $query->orderByDesc("id");
            },
        ]);

        return GlobalFunction::responseFunction(
            Message::RR_TAGGED_FISTO,
            $rr_transaction
        );
    }
}
