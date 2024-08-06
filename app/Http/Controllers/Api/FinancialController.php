<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Models\FinancialStatement;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Http\Resources\FinancialResource;
use App\Http\Requests\FinancialStatement\StoreRequest;
use App\Http\Requests\FinancialStatement\ImportRequest;

class FinancialController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $financial_statement = FinancialStatement::when(
            $status === "inactive",
            function ($query) {
                $query->onlyTrashed();
            }
        )

            ->useFilters()
            ->orderByDesc("updated_at")
            ->dynamicPaginate();

        $is_empty = $financial_statement->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        FinancialResource::collection($financial_statement);
        return GlobalFunction::responseFunction(
            Message::FINANCIAL_DISPLAY,
            $financial_statement
        );
    }

    public function store(StoreRequest $request)
    {
        $financial_statement = FinancialStatement::create([
            "name" => $request->name,
        ]);

        $financial_collect = new FinancialResource($financial_statement);

        return GlobalFunction::save(
            Message::FINANCIAL_SAVE,
            $financial_collect
        );
    }

    public function update(StoreRequest $request, $id)
    {
        $financial_statement = FinancialStatement::find($id);
        $is_exists = FinancialStatement::where("id", $id)->get();

        if ($is_exists->isEmpty()) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $financial_statement->update([
            "name" => $request->name,
        ]);
        return GlobalFunction::responseFunction(
            Message::FINANCIAL_UPDATE,
            $financial_statement
        );
    }

    public function destroy($id)
    {
        $financial_statement = FinancialStatement::where("id", $id)
            ->withTrashed()
            ->get();

        if ($financial_statement->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $financial_statement = FinancialStatement::withTrashed()->find($id);
        $is_active = FinancialStatement::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $financial_statement->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $financial_statement->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $financial_statement);
    }

    public function import(ImportRequest $request)
    {
        $import = $request->all();

        foreach ($import as $index) {
            $financial = FinancialStatement::create([
                "name" => $index["name"],
            ]);
        }

        return GlobalFunction::save(Message::FINANCIAL_SAVE, $import);
    }
}
