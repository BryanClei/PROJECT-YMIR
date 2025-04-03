<?php

namespace App\Http\Controllers\Api;

use App\Models\Type;
use App\Models\Credit;
use App\Response\Message;
use App\Models\AccountType;
use App\Models\AccountGroup;
use App\Models\AccountTitle;
use Illuminate\Http\Request;
use App\Models\NormalBalance;
use App\Models\AccountSubGroup;
use App\Models\AccountTitleUnit;
use App\Functions\GlobalFunction;
use App\Models\FinancialStatement;
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
            "account_title_unit",
            "request_type"
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
            "credit_id" => $request->credit_id,
            "credit_name" => $request->credit_name,
            "credit_code" => $request->credit_code,
            "request_id" => $request->request_id,
            "request_type" => $request->request_type,
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
            "credit_id" => $request->credit_id,
            "credit_name" => $request->credit_name,
            "credit_code" => $request->credit_code,
            "request_id" => $request->request_id,
            "request_type" => $request->request_type,
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
            $account_type = $index["account_type"];
            $account_group = $index["account_group"];
            $account_sub_group = $index["account_sub_group"];
            $financial_statement = $index["financial_statement"];
            $normal_balance = $index["normal_balance"];
            $account_title_unit = $index["account_title_unit"];
            $credit = $index["credit_name"];
            $request_type = $index["request_type"];

            $account_type_id = AccountType::where(
                "name",
                $account_type
            )->first();
            $account_group_id = AccountGroup::where(
                "name",
                $account_group
            )->first();
            $account_sub_group_id = AccountSubGroup::where(
                "name",
                $account_sub_group
            )->first();
            $financial_statement_id = FinancialStatement::where(
                "name",
                $financial_statement
            )->first();
            $normal_balance_id = NormalBalance::where(
                "name",
                $normal_balance
            )->first();
            $account_title_unit_id = AccountTitleUnit::where(
                "name",
                $account_title_unit
            )->first();
            $credit_id = Credit::where("name", $credit)->first();
            $request_type_id = Type::where("name", $request_type)->first();

            $account_title = AccountTitle::create([
                "name" => $index["name"],
                "code" => $index["code"],
                "account_type_id" => $account_type_id->id,
                "account_group_id" => $account_group_id->id,
                "account_sub_group_id" => $account_sub_group_id->id,
                "financial_statement_id" => $financial_statement_id->id,
                "normal_balance_id" => $normal_balance_id->id,
                "account_title_unit_id" => $account_title_unit_id->id,
                "credit_id" => $credit_id->id,
                "credit_name" => $credit_id->name,
                "credit_code" => $credit_id->code,
                "request_id" => $request_type_id->id,
                "request_type" => $request_type_id->name,
            ]);
        }

        return GlobalFunction::save(Message::ACCOUNT_TITLE_SAVE, $import);
    }
}
