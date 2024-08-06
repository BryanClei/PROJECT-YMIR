<?php

namespace App\Http\Controllers\Api;

use App\Models\SubUnit;
use App\Response\Message;
use App\Models\Department;
use Illuminate\Http\Request;
use App\Models\DepartmentUnit;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Http\Resources\DepartmentUnitResource;
use App\Http\Requests\DepartmentUnit\StoreRequest;
use App\Http\Resources\DepartmentUnitSaveResource;
use App\Http\Requests\DepartmentUnit\ImportRequest;

class DepartmentUnitController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $department_unit = DepartmentUnit::with("sub_unit", "department")
            ->when($status === "inactive", function ($query) {
                $query->onlyTrashed();
            })

            ->useFilters()
            ->orderByDesc("updated_at")
            ->dynamicPaginate();

        $is_empty = $department_unit->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        new DepartmentUnitResource($department_unit);
        return GlobalFunction::responseFunction(
            Message::DEPARTMENT_UNIT_DISPLAY,
            $department_unit
        );
    }

    public function store(StoreRequest $request)
    {
        $department_unit = DepartmentUnit::create([
            "code" => $request->code,
            "name" => $request->name,
            "department_id" => $request->department_id,
        ]);

        $uom_collect = new DepartmentUnitSaveResource($department_unit);

        return GlobalFunction::save(
            Message::DEPARTMENT_UNIT_SAVE,
            $uom_collect
        );
    }

    public function update(StoreRequest $request, $id)
    {
        $department_unit = DepartmentUnit::find($id);
        $is_exists = DepartmentUnit::where("id", $id)->get();

        if ($is_exists->isEmpty()) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $department_unit->update([
            "code" => $request->code,
            "name" => $request->name,
            "department_id" => $request->department_id,
        ]);
        return GlobalFunction::responseFunction(
            Message::DEPARTMENT_UNIT_UPDATE,
            $department_unit
        );
    }

    public function destroy($id)
    {
        $department_unit = DepartmentUnit::where("id", $id)
            ->withTrashed()
            ->get();

        if ($department_unit->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        $sub_unit = SubUnit::with("sub_unit")
            ->whereHas("sub_unit", function ($query) use ($id) {
                return $query->where("id", $id);
            })
            ->exists();

        if ($sub_unit) {
            return GlobalFunction::invalid(Message::IN_USE_DEPARTMENT);
        }

        $department_unit = DepartmentUnit::withTrashed()->find($id);
        $is_active = DepartmentUnit::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $department_unit->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $department_unit->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $department_unit);
    }

    public function import(ImportRequest $request)
    {
        $import = $request->all();

        foreach ($import as $index) {
            $department = $index["department"];

            $department_id = Department::where("name", $department)->first();

            $department = DepartmentUnit::create([
                "name" => $index["name"],
                "code" => $index["code"],
                "department_id" => $department_id->id,
            ]);
        }

        return GlobalFunction::save(Message::DEPARTMENT_UNIT_SAVE, $import);
    }
}
