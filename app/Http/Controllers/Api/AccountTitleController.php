<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use App\Models\AccountTitle;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Http\Resources\AccountTitleResource;
use App\Http\Requests\AccountTitle\StoreRequest;
use App\Http\Requests\AccountTitle\ImportRequest;

class AccountTitleController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $account_title = AccountTitle::with(
            "account_type",
            "account_group",
            "account_sub_group",
            "financial_statement",
            "normal_balance",
            "account_title_unit"
        )
            ->when($status === "inactive", function ($query) {
                $query->onlyTrashed();
            })

            ->useFilters()
            ->orderByDesc("updated_at")
            ->dynamicPaginate();

        $is_empty = $account_title->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        AccountTitleResource::collection($account_title);
        return GlobalFunction::responseFunction(
            Message::ACCOUNT_TITLE_DISPLAY,
            $account_title
        );
    }

    public function store(StoreRequest $request)
    {
        $account_title = AccountTitle::create([
            "name" => $request->name,
            "code" => $request->code,
            "account_type_id" => $request->account_type_id,
            "account_group_id" => $request->account_group_id,
            "account_sub_group_id" => $request->account_sub_group_id,
            "financial_statement_id" => $request->financial_statement_id,
            "normal_balance_id" => $request->normal_balance_id,
            "account_title_unit_id" => $request->account_title_unit_id,
        ]);

        $account_title_collect = new AccountTitleResource($account_title);

        return GlobalFunction::save(
            Message::ACCOUNT_TITLE_SAVE,
            $account_title_collect
        );
    }

    public function update(StoreRequest $request, $id)
    {
        $account_title = AccountTitle::find($id);
        $is_exists = AccountTitle::where("id", $id)->get();

        if ($is_exists->isEmpty()) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $account_title->update([
            "name" => $request->name,
            "code" => $request->code,
            "account_type_id" => $request->account_type_id,
            "account_group_id" => $request->account_group_id,
            "account_sub_group_id" => $request->account_sub_group_id,
            "financial_statement_id" => $request->financial_statement_id,
            "normal_balance_id" => $request->normal_balance_id,
            "account_title_unit_id" => $request->account_title_unit_id,
        ]);

        $account_title_collect = new AccountTitleResource($account_title);

        return GlobalFunction::responseFunction(
            Message::ACCOUNT_TITLE_UPDATE,
            $account_title_collect
        );
    }

    public function destroy($id)
    {
        $account_title = AccountTitle::where("id", $id)
            ->withTrashed()
            ->get();

        if ($account_title->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $account_title = AccountTitle::withTrashed()->find($id);
        $is_active = AccountTitle::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $account_title->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $account_title->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $account_title);
    }

    public function import(ImportRequest $request)
    {
        $import = $request->all();

        foreach ($import as $index) {
            $account_title = AccountTitle::create([
                "name" => $index["name"],
                "code" => $index["code"],
                "account_type_id" => $index["account_type_id"],
                "account_group_id" => $index["account_group_id"],
                "account_sub_group_id" => $index["account_sub_group_id"],
                "financial_statement_id" => $index["financial_statement_id"],
                "normal_balance_id" => $index["normal_balance_id"],
                "account_title_unit_id" => $index["account_title_unit_id"],
            ]);
        }

        return GlobalFunction::save(Message::ACCOUNT_TITLE_SAVE, $import);
    }
}
