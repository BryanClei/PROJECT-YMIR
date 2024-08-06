<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use Illuminate\Http\Request;
use App\Models\AccountTitleUnit;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Http\Resources\AccountTitleUnitResource;
use App\Http\Requests\AccountTitleUnit\StoreRequest;
use App\Http\Requests\AccountTitleUnit\ImportRequest;

class AccountTitleUnitController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $account_title_units = AccountTitleUnit::when(
            $status === "inactive",
            function ($query) {
                $query->onlyTrashed();
            }
        )
            ->useFilters()
            ->orderByDesc("updated_at")
            ->dynamicPaginate();

        $is_empty = $account_title_units->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        AccountTitleUnitResource::collection($account_title_units);
        return GlobalFunction::responseFunction(
            Message::ACCOUNT_TITLE_UNIT_DISPLAY,
            $account_title_units
        );
    }

    public function store(StoreRequest $request)
    {
        $account_title_units = AccountTitleUnit::create([
            "name" => $request->name,
        ]);

        $account_unit_collect = new AccountTitleUnitResource(
            $account_title_units
        );

        return GlobalFunction::save(
            Message::ACCOUNT_TITLE_UNIT_SAVE,
            $account_unit_collect
        );
    }

    public function update(StoreRequest $request, $id)
    {
        $account_title_units = AccountTitleUnit::find($id);
        $is_exists = AccountTitleUnit::where("id", $id)->get();

        if ($is_exists->isEmpty()) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $account_title_units->update([
            "name" => $request->name,
        ]);
        return GlobalFunction::responseFunction(
            Message::ACCOUNT_TITLE_UNIT_UPDATE,
            $account_title_units
        );
    }

    public function destroy($id)
    {
        $account_title_units = AccountTitleUnit::where("id", $id)
            ->withTrashed()
            ->get();

        if ($account_title_units->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $account_title_units = AccountTitleUnit::withTrashed()->find($id);
        $is_active = AccountTitleUnit::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $account_title_units->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $account_title_units->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $account_title_units);
    }

    public function import(ImportRequest $request)
    {
        $import = $request->all();

        foreach ($import as $index) {
            $account_title_units = AccountTitleUnit::create([
                "name" => $index["name"],
            ]);
        }

        return GlobalFunction::save(Message::ACCOUNT_TITLE_UNIT_SAVE, $import);
    }
}
