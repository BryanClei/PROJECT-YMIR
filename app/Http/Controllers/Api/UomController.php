<?php

namespace App\Http\Controllers\Api;

use App\Models\Uom;
use App\Response\Message;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Resources\UomResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Http\Requests\Uom\StoreRequest;
use App\Http\Requests\Uom\ImportRequest;

class UomController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $uom = Uom::when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })

            ->useFilters()
            ->orderByDesc("updated_at")
            ->dynamicPaginate();

        $is_empty = $uom->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        UomResource::collection($uom);
        return GlobalFunction::responseFunction(Message::UOM_DISPLAY, $uom);
    }

    public function store(StoreRequest $request)
    {
        $uom = Uom::create([
            "code" => $request->code,
            "name" => $request->name,
            "is_integer" => $request->is_integer,
        ]);

        $uom_collect = new UomResource($uom);

        return GlobalFunction::save(Message::UOM_SAVE, $uom_collect);
    }

    public function update(StoreRequest $request, $id)
    {
        $uom = Uom::find($id);
        $is_exists = Uom::where("id", $id)->get();

        if ($is_exists->isEmpty()) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $uom->update([
            "code" => $request->code,
            "name" => $request->name,
            "is_integer" => $request->is_integer,
        ]);
        return GlobalFunction::responseFunction(Message::UOM_UPDATE, $uom);
    }

    public function destroy($id)
    {
        $uom = Uom::where("id", $id)
            ->withTrashed()
            ->get();

        if ($uom->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $uom = Uom::withTrashed()->find($id);
        $is_active = Uom::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $uom->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $uom->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $uom);
    }

    public function import(ImportRequest $request)
    {
        $import = $request->all();

        foreach ($import as $index) {
            $company = Uom::create([
                "name" => $index["name"],
                "code" => $index["code"],
                "is_integer" => $index["is_integer"],
            ]);
        }

        return GlobalFunction::save(Message::UOM_SAVE, $import);
    }
}
