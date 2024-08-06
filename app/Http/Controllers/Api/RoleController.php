<?php

namespace App\Http\Controllers\Api;

use App\Models\Role;
use App\Response\Message;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Http\Requests\DisplayRequest;

class RoleController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $role = Role::when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })
            ->useFilters()
            ->orderByDesc("updated_at")
            ->dynamicPaginate();

        $is_empty = $role->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound();
        }
        RoleResource::collection($role);
        return GlobalFunction::responseFunction(Message::ROLE_DISPLAY, $role);
    }

    public function store(Request $request)
    {
        $access_permission = $request->access_permission;
        $accessConvertedToString = implode(",", $access_permission);

        $role = Role::create([
            "name" => $request->name,
            "access_permission" => $accessConvertedToString,
        ]);

        new RoleResource($role);

        return GlobalFunction::save(Message::ROLE_SAVE, $role);
    }

    public function update(Request $request, $id)
    {
        $role = Role::find($id);
        $is_exists = Role::where("id", $id)->get();

        if ($is_exists->isEmpty()) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }
        $access_permission = $request->access_permission;

        $access_permission = implode(",", $access_permission);

        $role->update([
            "name" => $request->name,
            "access_permission" => $access_permission,
        ]);
        return GlobalFunction::responseFunction(Message::ROLE_UPDATE, $role);
    }

    public function archived($id)
    {
        $role = Role::where("id", $id)
            ->withTrashed()
            ->get();

        if ($role->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $role = Role::withTrashed()->find($id);
        $is_active = Role::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $role->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $role->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $role);
    }
}
