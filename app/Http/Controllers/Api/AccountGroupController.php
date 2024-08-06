<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use App\Models\AccountGroup;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Http\Resources\AccountGroupResource;
use App\Http\Requests\AccountGroup\StoreRequest;
use App\Http\Requests\AccountGroup\ImportRequest;

class AccountGroupController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $account_group = AccountGroup::when($status === "inactive", function (
            $query
        ) {
            $query->onlyTrashed();
        })

            ->useFilters()
            ->orderByDesc("updated_at")
            ->dynamicPaginate();

        $is_empty = $account_group->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        AccountGroupResource::collection($account_group);
        return GlobalFunction::responseFunction(
            Message::ACCOUNT_GROUP_DISPLAY,
            $account_group
        );
    }

    public function store(StoreRequest $request)
    {
        $account_group = AccountGroup::create([
            "name" => $request->name,
        ]);

        $account_group_collect = new AccountGroupResource($account_group);

        return GlobalFunction::save(
            Message::ACCOUNT_GROUP_SAVE,
            $account_group_collect
        );
    }

    public function update(StoreRequest $request, $id)
    {
        $account_group = AccountGroup::find($id);
        $is_exists = AccountGroup::where("id", $id)->get();

        if ($is_exists->isEmpty()) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $account_group->update([
            "name" => $request->name,
        ]);
        return GlobalFunction::responseFunction(
            Message::ACCOUNT_GROUP_UPDATE,
            $account_group
        );
    }

    public function destroy($id)
    {
        $account_group = AccountGroup::where("id", $id)
            ->withTrashed()
            ->get();

        if ($account_group->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $account_group = AccountGroup::withTrashed()->find($id);
        $is_active = AccountGroup::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $account_group->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $account_group->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $account_group);
    }

    public function import(ImportRequest $request)
    {
        $import = $request->all();

        foreach ($import as $index) {
            $group = AccountGroup::create([
                "name" => $index["name"],
            ]);
        }

        return GlobalFunction::save(Message::ACCOUNT_GROUP_SAVE, $import);
    }
}
