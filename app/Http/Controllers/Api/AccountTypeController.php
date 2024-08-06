<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use App\Models\AccountType;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Http\Resources\AccountTypeResource;
use App\Http\Requests\AccountType\StoreRequest;
use App\Http\Requests\AccountType\ImportRequest;

class AccountTypeController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $account_type = AccountType::when($status === "inactive", function (
            $query
        ) {
            $query->onlyTrashed();
        })

            ->useFilters()
            ->orderByDesc("updated_at")
            ->dynamicPaginate();

        $is_empty = $account_type->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        AccountTypeResource::collection($account_type);
        return GlobalFunction::responseFunction(
            Message::ACCOUNT_TYPE_DISPLAY,
            $account_type
        );
    }

    public function store(StoreRequest $request)
    {
        $account_type = AccountType::create([
            "name" => $request->name,
        ]);

        $company_collect = new AccountTypeResource($account_type);

        return GlobalFunction::save(
            Message::ACCOUNT_TYPE_SAVE,
            $company_collect
        );
    }

    public function update(StoreRequest $request, $id)
    {
        $account_type = AccountType::find($id);
        $is_exists = AccountType::where("id", $id)->get();

        if ($is_exists->isEmpty()) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $account_type->update([
            "name" => $request->name,
        ]);
        return GlobalFunction::responseFunction(
            Message::ACCOUNT_TYPE_UPDATE,
            $account_type
        );
    }

    public function destroy($id)
    {
        $account_type = AccountType::where("id", $id)
            ->withTrashed()
            ->get();

        if ($account_type->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $account_type = AccountType::withTrashed()->find($id);
        $is_active = AccountType::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $account_type->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $account_type->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $account_type);
    }

    public function import(ImportRequest $request)
    {
        $import = $request->all();

        foreach ($import as $index) {
            $account_type = AccountType::create([
                "name" => $index["name"],
            ]);
        }

        return GlobalFunction::save(Message::ACCOUNT_TYPE_SAVE, $import);
    }
}
