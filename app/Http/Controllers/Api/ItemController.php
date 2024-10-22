<?php

namespace App\Http\Controllers\Api;

use App\Models\Uom;
use App\Models\Type;
use App\Models\Items;
use App\Response\Message;
use App\Models\AssetsItem;
use App\Models\Categories;
use Illuminate\Http\Request;
use App\Models\ItemWarehouse;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Http\Requests\DisplayRequest;
use App\Http\Requests\Item\StoreRequest;
use App\Http\Requests\Item\ImportRequest;

class ItemController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;
        $item_name = $request->item_name;

        $item = Items::with(
            "types",
            "uom",
            "small_tools",
            "warehouse.warehouse"
        )
            ->when($status === "inactive", function ($query) {
                $query->onlyTrashed();
            })
            ->useFilters()
            ->orderByDesc("code")
            ->dynamicPaginate();

        $is_empty = $item->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        ItemResource::collection($item);
        return GlobalFunction::responseFunction(Message::ITEM_DISPLAY, $item);

        // $item_collect = ItemResource::collection($item);
        // return GlobalFunction::responseFunction(
        //     Message::ITEM_DISPLAY,
        //     $item_collect
        // );
    }

    public function show(DisplayRequest $request, $id)
    {
        $status = $request->status;
        $item = Items::where("id", $id)
            ->with("types", "uom", "small_tools", "warehouse.warehouse")
            ->when($status === "inactive", function ($query) {
                $query->onlyTrashed();
            })
            ->useFilters()
            ->orderByDesc("code")
            ->dynamicPaginate();
        $collect = new ItemResource($item);

        return GlobalFunction::responseFunction(Message::ITEM_DISPLAY, $item);
    }

    public function store(StoreRequest $request)
    {
        $item = new Items([
            "code" => $request->code,
            "name" => $request->name,
            "uom_id" => $request->uom_id,
            "category_id" => $request->category_id,
            "type" => $request->type,
            "allowable" => $request->allowable,
        ]);

        $item->save();

        $warehouse = $request->warehouse;

        if ($warehouse) {
            foreach ($warehouse as $index => $values) {
                ItemWarehouse::create([
                    "item_id" => $item->id,
                    "warehouse_id" =>
                        $request["warehouse"][$index]["warehouse"],
                ]);
            }
        }

        $small_tools = $request->small_tools;

        if ($small_tools) {
            foreach ($small_tools as $index => $values) {
                AssetsItem::create([
                    "item_id" => $item->id,
                    "small_tools_id" =>
                        $request["small_tools"][$index]["small_tools_id"],
                    "code" => $request["small_tools"][$index]["code"],
                    "name" => $request["small_tools"][$index]["name"],
                ]);
            }
        }

        $item_collect = new ItemResource($item);

        return GlobalFunction::save(Message::ITEM_SAVE, $item_collect);
    }

    public function update(StoreRequest $request, $id)
    {
        $item = Items::find($id);
        $is_exists = Items::where("id", $id)->get();

        if ($is_exists->isEmpty()) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $item->update([
            "code" => $request->code,
            "name" => $request->name,
            "uom_id" => $request->uom_id,
            "category_id" => $request->category_id,
            "type" => $request->type,
            "allowable" => $request->allowable,
        ]);

        $Item_id = $item->id;

        $ids = ItemWarehouse::where("item_id", $Item_id)
            ->pluck("id")
            ->toArray();

        $warehouses = collect($request->warehouse)
            ->pluck("warehouse")
            ->toArray();

        ItemWarehouse::whereIn("id", $ids)->delete();

        if ($warehouses) {
            foreach ($warehouses as $warehouse) {
                ItemWarehouse::create([
                    "item_id" => $Item_id,
                    "warehouse_id" => $warehouse,
                ]);
            }
        }

        $asset_ids = AssetsItem::where("item_id", $Item_id)
            ->pluck("id")
            ->toArray();

        AssetsItem::whereIn("id", $asset_ids)->delete();
        $small_tools = $request->small_tools;
        if ($small_tools) {
            foreach ($small_tools as $index => $values) {
                AssetsItem::create([
                    "item_id" => $Item_id,
                    "small_tools_id" =>
                        $request["small_tools"][$index]["small_tools_id"],
                    "code" => $request["small_tools"][$index]["code"],
                    "name" => $request["small_tools"][$index]["name"],
                ]);
            }
        }

        $item_collect = new ItemResource($item);
        return GlobalFunction::responseFunction(
            Message::ITEM_UPDATE,
            $item_collect
        );
    }

    public function destroy($id)
    {
        $item = Items::where("id", $id)
            ->withTrashed()
            ->get();

        if ($item->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $item = Items::withTrashed()->find($id);
        $is_active = Items::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $item->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $item->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $item);
    }

    public function import(ImportRequest $request)
    {
        $import = $request->all();

        foreach ($import as $index) {
            $uom = $index["uom"];
            $category = $index["category"];
            $warehouse = $index["warehouse"];
            $type = $index["type"];

            $uom_id = Uom::where("name", $uom)->first();
            $type_id = Type::where("name", $type)->first();
            $warehouse_id = Warehouse::where("name", $warehouse)->first();
            $category_id = Categories::where("name", $category)->first();
            $allowable = Allowable::first();

            $department = Items::create([
                "name" => $index["name"],
                "code" => $index["code"],
                "type" => $type->id,
                "uom_id" => $uom_id->id,
                "category_id" => $category_id->id,
                "allowable" => $allowable,
            ]);

            $warehouse = $request->warehouse;

            if ($warehouse) {
                foreach ($warehouse as $index => $values) {
                    ItemWarehouse::create([
                        "item_id" => $item->id,
                        "warehouse_id" =>
                            $request["warehouse"][$index]["warehouse"],
                    ]);
                }
            }

            $small_tools = $request->small_tools;

            if ($small_tools) {
                foreach ($small_tools as $index => $values) {
                    AssetsItem::create([
                        "item_id" => $item->id,
                        "small_tools_id" =>
                            $request["small_tools"][$index]["small_tools_id"],
                        "code" => $request["small_tools"][$index]["code"],
                        "name" => $request["small_tools"][$index]["name"],
                    ]);
                }
            }
        }

        return GlobalFunction::save(Message::ITEM_SAVE, $import);
    }
}
