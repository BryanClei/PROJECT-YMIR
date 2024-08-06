<?php

namespace App\Http\Controllers\Api;

use App\Models\Company;
use App\Response\Message;
use App\Models\BusinessUnit;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Http\Resources\CompanyResource;
use App\Http\Requests\Company\StoreRequest;
use App\Http\Resources\CompanySaveResource;
use App\Http\Requests\Company\ImportRequest;

class CompanyController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $company = Company::with("business_unit")
            ->when($status === "inactive", function ($query) {
                $query->onlyTrashed();
            })

            ->useFilters()
            ->orderByDesc("updated_at")
            ->dynamicPaginate();

        $is_empty = $company->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        new CompanyResource($company);
        return GlobalFunction::responseFunction(
            Message::COMPANY_DISPLAY,
            $company
        );
    }

    public function store(StoreRequest $request)
    {
        $company = Company::create([
            "name" => $request->name,
            "code" => $request->code,
        ]);

        $company_collect = new CompanySaveResource($company);

        return GlobalFunction::save(Message::COMPANY_SAVE, $company_collect);
    }

    public function update(StoreRequest $request, $id)
    {
        $company = Company::find($id);
        $is_exists = Company::where("id", $id)->get();

        if ($is_exists->isEmpty()) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $company->update([
            "name" => $request->name,
            "code" => $request->code,
        ]);
        return GlobalFunction::responseFunction(
            Message::COMPANY_UPDATE,
            $company
        );
    }

    public function destroy($id)
    {
        $company = Company::where("id", $id)
            ->withTrashed()
            ->get();

        $business_unit = BusinessUnit::with("company")
            ->whereHas("company", function ($query) use ($id) {
                return $query->where("id", $id);
            })
            ->exists();

        if ($business_unit) {
            return GlobalFunction::invalid(Message::IN_USE);
        }

        if ($company->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $company = Company::withTrashed()->find($id);
        $is_active = Company::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $company->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $company->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $company);
    }

    public function import(ImportRequest $request)
    {
        $import = $request->all();

        foreach ($import as $index) {
            $company = Company::create([
                "name" => $index["name"],
                "code" => $index["code"],
            ]);
        }

        return GlobalFunction::save(Message::COMPANY_SAVE, $import);
    }
}
