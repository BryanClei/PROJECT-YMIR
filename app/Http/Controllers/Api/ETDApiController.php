<?php

namespace App\Http\Controllers\Api;

use App\Models\POItems;
use App\Models\RROrders;
use App\Models\Warehouse;
use App\Response\Message;
use Illuminate\Http\Request;
use App\Models\POTransaction;
use App\Models\PRTransaction;
use App\Models\RRTransaction;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Etd\SyncRequest;
use App\Http\Resources\ETDApiResource;

class ETDApiController extends Controller
{
    public function index(Request $request)
    {
        $warehouse_name = $request->system_name;
        $from_date = $request->from;
        $to_date = $request->to;

        $warehouse = Warehouse::where("name", $warehouse_name)
            ->get()
            ->first();

        if (!$warehouse) {
            return GlobalFunction::notFound(
                " Warehouse or " . Message::NOT_FOUND
            );
        }
        $w_id = $warehouse->id;

        $rr_transactions = RRTransaction::with([
            "rr_orders.order.uom",
            "rr_orders.pr_transaction",
            "rr_orders.po_transaction",
        ])
            ->whereHas("pr_transaction.order", function ($query) use ($w_id) {
                $query
                    ->where("warehouse_id", $w_id)
                    ->where("module_name", "Inventoriables");
            })
            ->whereHas("rr_orders", function ($query) {
                $query->where("etd_sync", 0);
            })
            ->when($from_date || $to_date, function ($query) use (
                $from_date,
                $to_date
            ) {
                return $query
                    ->when($from_date, function ($q) use ($from_date) {
                        return $q->whereDate("created_at", ">=", $from_date);
                    })
                    ->when($to_date, function ($q) use ($to_date) {
                        return $q->whereDate("created_at", "<=", $to_date);
                    });
            })
            ->get();

        if ($rr_transactions->isEmpty()) {
            return GlobalFunction::responseFunction(Message::NOT_FOUND);
        }

        return $rr_transactions;
    }

    public function etd_sync(SyncRequest $request)
    {
        $data = $request->all();
        $results = [];
        $sync = 1;

        foreach ($data as $item) {
            if (isset($item["item_id"])) {
                foreach ($item["item_id"] as $subItem) {
                    $rr_order_id = $subItem["id"];
                    $order = RROrders::where("id", $rr_order_id)->first();

                    $order->update([
                        "etd_sync" => $sync,
                    ]);

                    if ($order) {
                        $results[] = $order;
                    }
                }
            }
        }

        return GlobalFunction::responseFunction(
            Message::RR_ETD_ITEMS,
            $results
        );
    }
}
