<?php

namespace App\Http\Controllers\Api;

use App\Models\Warehouse;
use App\Response\Message;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Http\Resources\WarehouseResource;
use App\Http\Requests\Warehouse\StoreRequest;

class WarehouseController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $warehouse = Warehouse::when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })

            ->useFilters()
            ->orderByDesc("updated_at")
            ->dynamicPaginate();

        $is_empty = $warehouse->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        WarehouseResource::collection($warehouse);
        return GlobalFunction::responseFunction(
            Message::WAREHOUSE_DISPLAY,
            $warehouse
        );
    }

    public function store(StoreRequest $request)
    {
        $warehouse = Warehouse::create([
            "name" => $request->name,
            "code" => $request->code,
        ]);

        $company_collect = new WarehouseResource($warehouse);

        return GlobalFunction::save(Message::WAREHOUSE_SAVE, $company_collect);
    }

    public function update(StoreRequest $request, $id)
    {
        $warehouse = Warehouse::find($id);
        $is_exists = Warehouse::where("id", $id)->get();

        if ($is_exists->isEmpty()) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $warehouse->update([
            "name" => $request->name,
            "code" => $request->code,
        ]);
        return GlobalFunction::responseFunction(
            Message::WAREHOUSE_UPDATE,
            $warehouse
        );
    }

    public function destroy($id)
    {
        $warehouse = Warehouse::where("id", $id)
            ->withTrashed()
            ->get();

        if ($warehouse->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $warehouse = Warehouse::withTrashed()->find($id);
        $is_active = Warehouse::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $warehouse->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $warehouse->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $warehouse);
    }
}
