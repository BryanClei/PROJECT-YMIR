<?php

namespace App\Http\Controllers\Api;

use App\Models\Company;
use App\Response\Message;
use App\Models\Department;
use App\Models\BusinessUnit;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Http\Resources\BusinessResource;
use App\Http\Requests\BusinessUnit\StoreRequest;
use App\Http\Resources\BusinessUnitSaveResource;
use App\Http\Requests\BusinessUnit\ImportRequest;

class BusinessController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $business_unit = BusinessUnit::with("department", "company")
            ->when($status === "inactive", function ($query) {
                $query->onlyTrashed();
            })

            ->useFilters()
            ->orderByDesc("updated_at")
            ->dynamicPaginate();

        $is_empty = $business_unit->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        new BusinessResource($business_unit);
        return GlobalFunction::responseFunction(
            Message::BUSINESS_DISPLAY,
            $business_unit
        );
    }

    public function store(StoreRequest $request)
    {
        $business_unit = BusinessUnit::create([
            "name" => $request->name,
            "code" => $request->code,
            "company_id" => $request->company_id,
        ]);

        $business_collect = new BusinessUnitSaveResource($business_unit);

        return GlobalFunction::save(Message::BUSINESS_SAVE, $business_collect);
    }

    public function update(StoreRequest $request, $id)
    {
        $business_unit = BusinessUnit::find($id);
        $is_exists = BusinessUnit::where("id", $id)->get();

        if ($is_exists->isEmpty()) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $business_unit->update([
            "name" => $request->name,
            "code" => $request->code,
            "company_id" => $request->company_id,
        ]);
        return GlobalFunction::responseFunction(
            Message::BUSINESS_UPDATE,
            $business_unit
        );
    }

    public function destroy($id)
    {
        $business_unit = BusinessUnit::where("id", $id)
            ->withTrashed()
            ->get();

        if ($business_unit->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $department = Department::with("department")
            ->whereHas("department", function ($query) use ($id) {
                return $query->where("id", $id);
            })
            ->exists();

        if ($department) {
            return GlobalFunction::invalid(Message::IN_USE_BUSINESS_UNIT);
        }

        $business_unit = BusinessUnit::withTrashed()->find($id);
        $is_active = BusinessUnit::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $business_unit->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $business_unit->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $business_unit);
    }

    public function import(ImportRequest $request)
    {
        $import = $request->all();

        foreach ($import as $index) {
            $company = $index["company"];

            $company_id = Company::where("name", $company)->first();

            $business_unit = BusinessUnit::create([
                "name" => $index["name"],
                "code" => $index["code"],
                "company_id" => $company_id->id,
            ]);
        }

        return GlobalFunction::save(Message::BUSINESS_SAVE, $import);
    }
}
