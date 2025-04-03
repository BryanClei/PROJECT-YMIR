<?php

namespace App\Http\Controllers\Api;

use App\Models\Suppliers;
use App\Response\Message;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Http\Resources\SupplierResource;
use App\Http\Requests\Suppliers\StoreRequest;
use App\Http\Requests\Suppliers\ImportRequest;

class SupplierController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $suppliers = Suppliers::when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })

            ->useFilters()
            ->orderByDesc("updated_at")
            ->dynamicPaginate();

        $is_empty = $suppliers->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        SupplierResource::collection($suppliers);
        return GlobalFunction::responseFunction(
            Message::SUPPLIER_DISPLAY,
            $suppliers
        );
    }

    public function store(StoreRequest $request)
    {
        $suppliers = Suppliers::create([
            "code" => $request->code,
            "name" => $request->name,
            "type" => $request->type,
            "term" => $request->term,
            // "address_1" => $request->address_1,
            // "address_2" => $request->address_2,
        ]);

        $supplier_collect = new SupplierResource($suppliers);

        return GlobalFunction::save(Message::SUPPLIER_SAVE, $supplier_collect);
    }

    public function update(StoreRequest $request, $id)
    {
        $suppliers = Suppliers::find($id);
        $is_exists = Suppliers::where("id", $id)->get();

        if ($is_exists->isEmpty()) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $suppliers->update([
            "code" => $request->code,
            "name" => $request->name,
            "type" => $request->type,
            "term" => $request->term,
            // "address_1" => $request->address_1,
            // "address_2" => $request->address_2,
        ]);
        return GlobalFunction::responseFunction(
            Message::SUPPLIER_UPDATE,
            $suppliers
        );
    }

    public function destroy($id)
    {
        $suppliers = Suppliers::where("id", $id)
            ->withTrashed()
            ->get();

        if ($suppliers->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $suppliers = Suppliers::withTrashed()->find($id);
        $is_active = Suppliers::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $suppliers->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $suppliers->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $suppliers);
    }

    public function import(ImportRequest $request)
    {
        $import = $request->all();

        foreach ($import as $index) {
            $supplier = Suppliers::create([
                "name" => $index["name"],
                "code" => $index["code"],
                "type" => $index["type"],
                "term" => $index["term"],
                // "address_1" => $index["address_1"],
                // "address_2" => $index["address_2"],
            ]);
        }

        return GlobalFunction::save(Message::SUPPLIER_SAVE, $import);
    }
}
