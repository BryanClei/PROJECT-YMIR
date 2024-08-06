<?php

namespace App\Http\Controllers\Api;

use App\Models\SubUnit;
use App\Models\Location;
use App\Response\Message;
use Illuminate\Http\Request;
use App\Models\DepartmentUnit;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Http\Resources\SubUnitResource;
use App\Http\Requests\SubUnit\StoreRequest;
use App\Http\Resources\SubUnitSaveResource;
use App\Http\Requests\SubUnit\ImportRequest;

class SubUnitController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;
        $subunit = SubUnit::with("locations", "department_unit")
            ->when($status === "inactive", function ($query) {
                $query->onlyTrashed();
            })

            ->useFilters()
            ->orderByDesc("updated_at")
            ->dynamicPaginate();

        $is_empty = $subunit->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        new SubUnitResource($subunit);
        return GlobalFunction::responseFunction(
            Message::SUB_UNIT_DISPLAY,
            $subunit
        );
    }

    public function store(StoreRequest $request)
    {
        $subunit = SubUnit::create([
            "name" => $request->name,
            "code" => $request->code,
            "department_unit_id" => $request->department_unit_id,
        ]);
        $subunit_collect = new SubUnitSaveResource($subunit);
        return GlobalFunction::save(Message::SUB_UNIT_SAVE, $subunit_collect);
    }

    public function update(StoreRequest $request, $id)
    {
        $subunit = SubUnit::find($id);
        $is_exists = SubUnit::where("id", $id)->get();

        if ($is_exists->isEmpty()) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $subunit->update([
            "name" => $request->name,
            "code" => $request->code,
            "department_unit_id" => $request->department_unit_id,
        ]);

        $subunit_collect = new SubUnitSaveResource($subunit);
        return GlobalFunction::responseFunction(
            Message::SUB_UNIT_UPDATE,
            $subunit
        );
    }

    public function destroy($id)
    {
        $subunit = SubUnit::where("id", $id)
            ->withTrashed()
            ->get();

        if ($subunit->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        $location = Location::with("locations")
            ->whereHas("locations", function ($query) use ($id) {
                return $query->where("id", $id);
            })
            ->exists();

        if ($location) {
            return GlobalFunction::invalid(Message::IN_USE_DEPARTMENT);
        }

        $subunit = SubUnit::withTrashed()->find($id);
        $is_active = SubUnit::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $subunit->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $subunit->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $subunit);
    }

    public function import(ImportRequest $request)
    {
        $import = $request->all();

        foreach ($import as $index) {
            $department_unit = $index["department_unit"];

            $department_unit_id = DepartmentUnit::where(
                "name",
                $department_unit
            )->first();

            $department = SubUnit::create([
                "name" => $index["name"],
                "code" => $index["code"],
                "department_unit_id" => $department_unit_id->id,
            ]);
        }

        return GlobalFunction::save(Message::DEPARTMENT_UNIT_SAVE, $import);
    }
}
