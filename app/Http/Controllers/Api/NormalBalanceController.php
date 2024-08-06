<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use Illuminate\Http\Request;
use App\Models\NormalBalance;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Http\Resources\NormalBalanceResource;
use App\Http\Requests\NormalBalance\StoreRequest;
use App\Http\Requests\NormalBalance\ImportRequest;

class NormalBalanceController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $normal_balance = NormalBalance::when($status === "inactive", function (
            $query
        ) {
            $query->onlyTrashed();
        })

            ->useFilters()
            ->orderByDesc("updated_at")
            ->dynamicPaginate();

        $is_empty = $normal_balance->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        NormalBalanceResource::collection($normal_balance);
        return GlobalFunction::responseFunction(
            Message::NORMAL_BALANCE_DISPLAY,
            $normal_balance
        );
    }

    public function store(StoreRequest $request)
    {
        $normal_balance = NormalBalance::create([
            "name" => $request->name,
        ]);

        $account_group_collect = new NormalBalanceResource($normal_balance);

        return GlobalFunction::save(
            Message::NORMAL_BALANCE_SAVE,
            $account_group_collect
        );
    }

    public function update(StoreRequest $request, $id)
    {
        $normal_balance = NormalBalance::find($id);
        $is_exists = NormalBalance::where("id", $id)->get();

        if ($is_exists->isEmpty()) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $normal_balance->update([
            "name" => $request->name,
        ]);
        return GlobalFunction::responseFunction(
            Message::NORMAL_BALANCE_UPDATE,
            $normal_balance
        );
    }

    public function destroy($id)
    {
        $normal_balance = NormalBalance::where("id", $id)
            ->withTrashed()
            ->get();

        if ($normal_balance->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $normal_balance = NormalBalance::withTrashed()->find($id);
        $is_active = NormalBalance::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $normal_balance->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $normal_balance->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $normal_balance);
    }

    public function import(ImportRequest $request)
    {
        $import = $request->all();

        foreach ($import as $index) {
            $normal_balance = NormalBalance::create([
                "name" => $index["name"],
            ]);
        }

        return GlobalFunction::save(Message::NORMAL_BALANCE_SAVE, $import);
    }
}
