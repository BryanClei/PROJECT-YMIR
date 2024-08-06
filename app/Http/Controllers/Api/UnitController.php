<?php

namespace App\Http\Controllers\Api;

use App\Models\Units;
use App\Response\Message;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Resources\UnitResource;
use App\Http\Requests\DisplayRequest;
use App\Http\Requests\Unit\StoreRequest;

class UnitController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $unit = Units::when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })

            ->useFilters()
            ->orderByDesc("updated_at")
            ->dynamicPaginate();

        $is_empty = $unit->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        UnitResource::collection($unit);
        return GlobalFunction::responseFunction(Message::UNIT_DISPLAY, $unit);
    }

    public function store(StoreRequest $request)
    {
        $unit = Units::create([
            "name" => $request->name,
        ]);

        $account_group_collect = new UnitResource($unit);

        return GlobalFunction::save(Message::UNIT_SAVE, $account_group_collect);
    }

    public function update(StoreRequest $request, $id)
    {
        $unit = Units::find($id);
        $is_exists = Units::where("id", $id)->get();

        if ($is_exists->isEmpty()) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $unit->update([
            "name" => $request->name,
        ]);
        return GlobalFunction::responseFunction(Message::UNIT_UPDATE, $unit);
    }

    public function destroy($id)
    {
        $unit = Units::where("id", $id)
            ->withTrashed()
            ->get();

        if ($unit->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $unit = Units::withTrashed()->find($id);
        $is_active = Units::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $unit->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $unit->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $unit);
    }
}
