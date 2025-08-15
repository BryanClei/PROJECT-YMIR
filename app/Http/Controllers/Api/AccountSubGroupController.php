<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use Illuminate\Http\Request;
use App\Models\AccountSubGroup;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Http\Resources\AccountSubGroupResource;
use App\Http\Requests\AccountSubGroup\StoreRequest;
use App\Http\Requests\AccountSubGroup\ImportRequest;

class AccountSubGroupController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $account_sub_group = AccountSubGroup::when(
            $status === "inactive",
            function ($query) {
                $query->onlyTrashed();
            }
        )
            ->useFilters()
            ->orderByDesc("updated_at")
            ->dynamicPaginate();

        $is_empty = $account_sub_group->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        AccountSubGroupResource::collection($account_sub_group);
        return GlobalFunction::responseFunction(
            Message::ACCOUNT_SUB_GROUP_DISPLAY,
            $account_sub_group
        );
    }

    public function store(StoreRequest $request)
    {
        $account_sub_group = AccountSubGroup::create([
            "name" => $request->name,
        ]);

        GlobalFunction::master_logs(
            "Account Title Settings",
            "Account Sub Groups",
            "Created",
            "Created new sub account group: {$request->name}.",
            [],
            $account_sub_group->toArray()
        );

        $account_group_collect = new AccountSubGroupResource(
            $account_sub_group
        );

        return GlobalFunction::save(
            Message::ACCOUNT_SUB_GROUP_SAVE,
            $account_group_collect
        );
    }

    public function update(StoreRequest $request, $id)
    {
        $account_sub_group = AccountSubGroup::find($id);
        $is_exists = AccountSubGroup::where("id", $id)->get();

        if ($is_exists->isEmpty()) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $previous_data = $account_sub_group->getOriginal();

        $account_sub_group->update([
            "name" => $request->name,
        ]);

        GlobalFunction::master_logs(
            "Account Title Settings",
            "Account Sub Groups",
            "Updated",
            "Updated sub account group: {$account_sub_group->name}.",
            $previous_data,
            $account_sub_group->toArray()
        );

        return GlobalFunction::responseFunction(
            Message::ACCOUNT_SUB_GROUP_UPDATE,
            $account_sub_group
        );
    }

    public function destroy($id)
    {
        $account_sub_group = AccountSubGroup::where("id", $id)
            ->withTrashed()
            ->get();

        if ($account_sub_group->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $account_sub_group = AccountSubGroup::withTrashed()->find($id);
        $is_active = AccountSubGroup::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $account_sub_group->delete();
            $message = Message::ARCHIVE_STATUS;
            $action = "Archived";
        } else {
            $account_sub_group->restore();
            $message = Message::RESTORE_STATUS;
            $action = "Restored";
        }

        GlobalFunction::master_logs(
            "Account Title Settings",
            "Account Groups",
            $action,
            "{$action} account sub group: {$account_sub_group["name"]}.",
            [],
            []
        );

        return GlobalFunction::responseFunction($message, $account_sub_group);
    }

    public function import(ImportRequest $request)
    {
        $import = $request->all();

        foreach ($import as $index) {
            $subgroup = AccountSubGroup::create([
                "name" => $index["name"],
            ]);

            $new_records[] = $subgroup->toArray();
        }

        GlobalFunction::master_logs(
            "Account Title Settings",
            "Account Sub Groups",
            "Imported",
            "Imported " . count($new_records) . " account sub groups.",
            [],
            $new_records
        );

        return GlobalFunction::save(Message::ACCOUNT_SUB_GROUP_SAVE, $import);
    }
}
