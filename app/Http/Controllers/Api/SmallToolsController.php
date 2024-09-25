<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use App\Models\SmallTools;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Http\Resources\SmallToolsResource;
use App\Http\Requests\SmallTools\StoreRequest;
use App\Http\Requests\SmallTools\UpdateRequest;

class SmallToolsController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $small_tools = SmallTools::when($status === "inactive", function (
            $query
        ) {
            $query->onlyTrashed();
        })
            ->useFilters()
            ->orderByDesc("updated_at")
            ->dynamicPaginate();

        $is_empty = $small_tools->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        SmallToolsResource::collection($small_tools);
        return GlobalFunction::responseFunction(
            Message::SMALL_TOOLS_DISPLAY,
            $small_tools
        );
    }

    public function store(StoreRequest $request)
    {
        $small_tools = SmallTools::create([
            "name" => $request->name,
            "code" => $request->code,
        ]);

        $small_tools_collect = new SmallToolsResource($small_tools);

        return GlobalFunction::save(
            Message::SMALL_TOOLS_SAVE,
            $small_tools_collect
        );
    }

    public function update(UpdateRequest $request, $id)
    {
        $small_tools = SmallTools::find($id);
        $is_exists = SmallTools::where("id", $id)->get();

        if ($is_exists->isEmpty()) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $small_tools->update([
            "name" => $request->name,
        ]);
        return GlobalFunction::responseFunction(
            Message::SMALL_TOOLS_UPDATE,
            $small_tools
        );
    }

    public function destroy($id)
    {
        $small_tools = SmallTools::where("id", $id)
            ->withTrashed()
            ->get();

        if ($small_tools->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $small_tools = SmallTools::withTrashed()->find($id);
        $is_active = SmallTools::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $small_tools->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $small_tools->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $small_tools);
    }
}
