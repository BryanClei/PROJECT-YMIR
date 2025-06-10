<?php

namespace App\Http\Controllers\Api;

use App\Models\POItems;
use App\Models\Warehouse;
use App\Response\Message;
use Illuminate\Http\Request;
use App\Models\POTransaction;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Umd\SyncRequest;

class FedoraApiController extends Controller
{
    public function index(Request $request)
    {
        $warehouse_name = $request->system_name;
        $from_date = $request->from;
        $to_date = $request->to;

        $warehouse = Warehouse::where("name", $warehouse_name)->first();

        if (!$warehouse) {
            return GlobalFunction::notFound(
                " Warehouse or " . Message::NOT_FOUND
            );
        }
        $w_id = $warehouse->id;

        $po_transactions = POTransaction::with(["order.uom", "pr_transaction"])
            ->whereHas("order", function ($query) use ($w_id) {
                $query
                    ->where("warehouse_id", $w_id)
                    ->where("module_name", "Inventoriables");
            })
            ->whereNotNull("approved_at")
            ->where(function ($query) use ($from_date, $to_date) {
                $query
                    ->when($from_date, function ($q) use ($from_date) {
                        return $q->whereDate("approved_at", ">=", $from_date);
                    })
                    ->when($to_date, function ($q) use ($to_date) {
                        return $q->whereDate("approved_at", "<=", $to_date);
                    });
            })
            ->get();

        if ($po_transactions->isEmpty()) {
            return GlobalFunction::responseFunction(Message::NOT_FOUND);
        }

        return $po_transactions;
    }

    public function um_dry(Request $request)
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

        $po_transactions = POTransaction::with(["order.uom", "pr_transaction"])
            ->whereHas("order", function ($query) use ($w_id) {
                $query
                    ->where("umd_sync", 0)
                    ->where("warehouse_id", $w_id)
                    ->where("module_name", "Inventoriables");
            })
            ->whereNotNull("approved_at")
            ->where(function ($query) use ($from_date, $to_date) {
                $query
                    ->when($from_date, function ($q) use ($from_date) {
                        return $q->whereDate("approved_at", ">=", $from_date);
                    })
                    ->when($to_date, function ($q) use ($to_date) {
                        return $q->whereDate("approved_at", "<=", $to_date);
                    });
            })
            ->get();

        if ($po_transactions->isEmpty()) {
            return GlobalFunction::responseFunction(Message::NOT_FOUND);
        }

        return $po_transactions;
    }

    public function elixir_pharmacy(Request $request)
    {
        $warehouse_name = $request->system_name;
        $from_date = $request->from;
        $to_date = $request->to;

        $warehouse = Warehouse::where("name", $warehouse_name)->first();

        if (!$warehouse) {
            return GlobalFunction::notFound(
                " Warehouse or " . Message::NOT_FOUND
            );
        }
        $w_id = $warehouse->id;

        $po_transactions = POTransaction::with(["order.uom", "pr_transaction"])
            ->whereHas("order", function ($query) use ($w_id) {
                $query
                    ->where("warehouse_id", $w_id)
                    ->where("module_name", "Inventoriables");
            })
            ->whereNotNull("approved_at")
            ->where(function ($query) use ($from_date, $to_date) {
                $query
                    ->when($from_date, function ($q) use ($from_date) {
                        return $q->whereDate("approved_at", ">=", $from_date);
                    })
                    ->when($to_date, function ($q) use ($to_date) {
                        return $q->whereDate("approved_at", "<=", $to_date);
                    });
            })
            ->get();

        if ($po_transactions->isEmpty()) {
            return GlobalFunction::responseFunction(Message::NOT_FOUND);
        }

        return $po_transactions;
    }

    public function umd_sync(SyncRequest $request)
    {
        $data = $request->all();
        $results = [];
        $sync = 1;

        foreach ($data as $item) {
            if (isset($item["item_id"])) {
                foreach ($item["item_id"] as $subItem) {
                    $rr_order_id = $subItem["id"];
                    $order = POItems::where("id", $rr_order_id)->first();

                    $order->update([
                        "umd_sync" => $sync,
                    ]);

                    if ($order) {
                        $results[] = $order;
                    }
                }
            }
        }

        return GlobalFunction::responseFunction(Message::SYNC_ORDERS, $results);
    }

    public function dynamic_api(Request $request)
    {
        $warehouse_name = $request->system_name;
        $from_date = $request->from;
        $to_date = $request->to;

        $warehouse = Warehouse::where("name", $warehouse_name)->first();

        if (!$warehouse) {
            return GlobalFunction::notFound(
                " Warehouse or " . Message::NOT_FOUND
            );
        }
        $w_id = $warehouse->id;

        $po_transactions = POTransaction::with([
            "order" => function ($query) {
                $query->where("system_sync", 0);
            },
            "order.uom",
            "pr_transaction",
        ])
            ->whereHas("order", function ($query) use ($w_id) {
                $query
                    ->where("system_sync", 0)
                    ->where("warehouse_id", $w_id)
                    ->where("module_name", "Inventoriables");
            })
            ->whereNotNull("approved_at")
            ->where(function ($query) use ($from_date, $to_date) {
                $query
                    ->when($from_date, function ($q) use ($from_date) {
                        return $q->whereDate("approved_at", ">=", $from_date);
                    })
                    ->when($to_date, function ($q) use ($to_date) {
                        return $q->whereDate("approved_at", "<=", $to_date);
                    });
            })
            ->get();

        if ($po_transactions->isEmpty()) {
            return GlobalFunction::responseFunction(Message::NOT_FOUND);
        }

        return $po_transactions;
    }

    public function dynamic_sync(SyncRequest $request)
    {
        $data = $request->all();
        $results = [];
        $sync = 1;

        foreach ($data as $item) {
            if (isset($item["item_id"])) {
                foreach ($item["item_id"] as $subItem) {
                    $rr_order_id = $subItem["id"];
                    $order = POItems::where("id", $rr_order_id)->first();

                    $order->update([
                        "system_sync" => $sync,
                    ]);

                    if ($order) {
                        $results[] = $order;
                    }
                }
            }
        }

        return GlobalFunction::responseFunction(Message::SYNC_ORDERS, $results);
    }
}
