<?php

namespace App\Http\Controllers\Api;

use App\Models\Type;
use App\Response\Message;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Resources\TypeResource;
use App\Http\Requests\DisplayRequest;
use App\Http\Requests\Type\StoreRequest;

class TypeController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $type = Type::when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })

            ->useFilters()
            ->orderByDesc("updated_at")
            ->dynamicPaginate();

        $is_empty = $type->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        TypeResource::collection($type);
        return GlobalFunction::responseFunction(Message::TYPE_DISPLAY, $type);
    }

    public function store(StoreRequest $request)
    {
        $type = Type::create([
            "name" => $request->name,
        ]);

        $account_group_collect = new TypeResource($type);

        return GlobalFunction::save(Message::TYPE_SAVE, $account_group_collect);
    }

    public function update(StoreRequest $request, $id)
    {
        $type = Type::find($id);
        $is_exists = Type::where("id", $id)->get();

        if ($is_exists->isEmpty()) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $type->update([
            "name" => $request->name,
        ]);
        return GlobalFunction::responseFunction(Message::TYPE_UPDATE, $type);
    }

    public function destroy($id)
    {
        $type = Type::where("id", $id)
            ->withTrashed()
            ->get();

        if ($type->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $type = Type::withTrashed()->find($id);
        $is_active = Type::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $type->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $type->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $type);
    }
}
