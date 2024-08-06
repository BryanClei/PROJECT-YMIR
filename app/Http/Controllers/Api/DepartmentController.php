<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use App\Models\Department;
use App\Models\BusinessUnit;
use Illuminate\Http\Request;
use App\Models\DepartmentUnit;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Http\Resources\DepartmentResource;
use App\Http\Requests\Department\StoreRequest;
use App\Http\Resources\DepartmentSaveResource;
use App\Http\Requests\Department\ImportRequest;

class DepartmentController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $department = Department::with("department_unit", "business_unit")
            ->when($status === "inactive", function ($query) {
                $query->onlyTrashed();
            })

            ->useFilters()
            ->orderByDesc("updated_at")
            ->dynamicPaginate();

        $is_empty = $department->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        new DepartmentResource($department);

        return GlobalFunction::responseFunction(
            Message::DEPARTMENT_DISPLAY,
            $department
        );
    }

    public function store(StoreRequest $request)
    {
        $department = Department::create([
            "name" => $request->name,
            "code" => $request->code,
            "business_unit_id" => $request->business_unit_id,
        ]);

        $company_collect = new DepartmentSaveResource($department);

        return GlobalFunction::save(Message::DEPARTMENT_SAVE, $company_collect);
    }

    public function update(StoreRequest $request, $id)
    {
        $department = Department::find($id);
        $is_exists = Department::where("id", $id)->get();

        if ($is_exists->isEmpty()) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $department->update([
            "name" => $request->name,
            "code" => $request->code,
            "business_unit_id" => $request->business_unit_id,
        ]);
        return GlobalFunction::responseFunction(
            Message::DEPARTMENT_UPDATE,
            $department
        );
    }

    public function destroy($id)
    {
        $department = Department::where("id", $id)
            ->withTrashed()
            ->get();

        if ($department->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        $department_unit = DepartmentUnit::with("department")
            ->whereHas("department", function ($query) use ($id) {
                return $query->where("id", $id);
            })
            ->exists();

        if ($department_unit) {
            return GlobalFunction::invalid(Message::IN_USE_DEPARTMENT);
        }

        $department = Department::withTrashed()->find($id);
        $is_active = Department::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $department->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $department->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $department);
    }
    public function import(ImportRequest $request)
    {
        $import = $request->all();

        foreach ($import as $index) {
            $business_unit = $index["business_unit"];

            $business_unit_id = BusinessUnit::where(
                "name",
                $business_unit
            )->first();

            $department = Department::create([
                "name" => $index["name"],
                "code" => $index["code"],
                "business_unit_id" => $business_unit_id->id,
            ]);
        }

        return GlobalFunction::save(Message::DEPARTMENT_SAVE, $import);
    }
}
