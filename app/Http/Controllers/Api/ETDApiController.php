<?php

namespace App\Http\Controllers\Api;

use App\Models\POItems;
use App\Models\Warehouse;
use App\Response\Message;
use Illuminate\Http\Request;
use App\Models\POTransaction;
use App\Models\PRTransaction;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Resources\ETDApiResource;

class ETDApiController extends Controller
{
    public function index(Request $request)
    {
        $warehouse_name = $request->system_name;
        $from = $request->from;
        $to = $request->to;

        $warehouse = Warehouse::where("name", $warehouse_name)
            ->get()
            ->first();

        if (!$warehouse) {
            return GlobalFunction::notFound(
                " Warehouse or " . Message::NOT_FOUND
            );
        }
        $w_id = $warehouse->id;

        $data = POItems::with([
            "po_transaction",
            "po_transaction.pr_transaction",
        ])
            ->whereHas("po_transaction", function ($query) use (
                $w_id,
                $from,
                $to
            ) {
                $query
                    ->where("type_name", "Inventoriable")
                    ->where("status", "For Receiving")
                    ->where("warehouse_id", $w_id)
                    ->whereNotNull("approved_at")
                    ->when($from, function ($query) use ($from) {
                        return $query->whereDate("approved_at", ">=", $from);
                    })
                    ->when($to, function ($query) use ($to) {
                        return $query->whereDate("approved_at", "<=", $to);
                    });
            })
            ->get();

        if ($data->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return ETDApiResource::collection($data);
    }
}
