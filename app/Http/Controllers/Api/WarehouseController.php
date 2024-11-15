<?php

namespace App\Http\Controllers\Api;

use DB;
use App\Models\Warehouse;
use App\Response\Message;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Models\WarehouseAccountTitles;
use App\Http\Resources\WarehouseResource;
use App\Http\Requests\Warehouse\StoreRequest;

class WarehouseController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $warehouse = Warehouse::with("warehouseAccountTitles")
            ->when($status === "inactive", function ($query) {
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
        try {
            DB::beginTransaction();

            $warehouse = Warehouse::create([
                "name" => $request->name,
                "code" => $request->code,
                "url" => $request->url,
                "token" => $request->token,
            ]);

            foreach ($request->account_titles as $accountTitle) {
                WarehouseAccountTitles::create([
                    "warehouse_id" => $warehouse->id,
                    "account_title_id" => $accountTitle["account_title_id"],
                    "transaction_type" => $accountTitle["transaction_type"],
                ]);
            }

            \DB::commit();

            $warehouse->load("warehouseAccountTitles.accountTitle");
            return GlobalFunction::save(
                Message::WAREHOUSE_SAVE,
                new WarehouseResource($warehouse)
            );
        } catch (\Exception $e) {
            \DB::rollBack();
            return GlobalFunction::error($e);
        }
    }

    public function update(StoreRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $warehouse = Warehouse::find($id);

            if (!$warehouse) {
                return GlobalFunction::invalid(Message::INVALID_ACTION);
            }

            // Update warehouse details
            $warehouse->update([
                "name" => $request->name,
                "code" => $request->code,
                "url" => $request->url,
                "token" => $request->token,
            ]);

            // Update or create warehouse account titles
            if ($request->has("account_titles")) {
                foreach ($request->account_titles as $accountTitle) {
                    // Check if there's an existing record for this account title
                    $warehouseAccountTitle = WarehouseAccountTitles::where(
                        "warehouse_id",
                        $id
                    )
                        ->where(
                            "account_title_id",
                            $accountTitle["account_title_id"]
                        )
                        ->first();

                    if ($warehouseAccountTitle) {
                        // Update existing record
                        $warehouseAccountTitle->update([
                            "transaction_type" =>
                                $accountTitle["transaction_type"],
                        ]);
                    } else {
                        // Create new record if it doesn't exist
                        WarehouseAccountTitles::create([
                            "warehouse_id" => $id,
                            "account_title_id" =>
                                $accountTitle["account_title_id"],
                            "transaction_type" =>
                                $accountTitle["transaction_type"],
                        ]);
                    }
                }
            }

            DB::commit();

            $warehouse->load("warehouseAccountTitles.accountTitle");
            return GlobalFunction::responseFunction(
                Message::WAREHOUSE_UPDATE,
                new WarehouseResource($warehouse)
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return GlobalFunction::error($e->getMessage());
        }
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
