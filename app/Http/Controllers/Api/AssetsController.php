<?php

namespace App\Http\Controllers\API;

use App\Models\Assets;
use App\Response\Message;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Http\Resources\AssetsResource;
use App\Http\Requests\Asset\StoreRequest;
use App\Http\Requests\Asset\ImportRequest;
use App\Http\Requests\Asset\UpdateRequest;

class AssetsController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $asset = Assets::when($status === "inactive", function ($query) {
                $query->onlyTrashed();
            })

            ->useFilters()
            ->orderByDesc("updated_at")
            ->dynamicPaginate();

        $is_empty = $asset->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        new AssetsResource($asset);
        return GlobalFunction::responseFunction(
            Message::ASSET_DISPLAY,
            $asset
        );
    }

    public function store(StoreRequest $request){

        $asset = Assets::create([
            "name" => $request->name,
            "tag_number" => $request->tag_number
        ]);

        $asset_collect = new AssetsResource($asset);

        return GlobalFunction::save(Message::ASSET_SAVE, $asset_collect);
    }

    public function update(UpdateRequest $request, $id)
    {
        $asset = Assets::find($id);
        $is_exists = Assets::where("id", $id)->get();

        if ($is_exists->isEmpty()) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $asset->update([
            "name" => $request->name,
            "tag_number" => $request->tag_number,
        ]);

        return GlobalFunction::responseFunction(
            Message::ASSET_UPDATE,
            $asset
        );
    }

    public function destroy($id)
    {
        $asset = Assets::where("id", $id)
            ->withTrashed()
            ->get();

        if ($asset->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $asset = Assets::withTrashed()->find($id);
        $is_active = Assets::withTrashed()
            ->where("id", $id)
            ->first();

        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $asset->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $asset->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $asset);
    }

    public function import(ImportRequest $request)
    {
        $import = $request->all();

        foreach ($import as $index) {
            $asset = Assets::create([
                "name" => $index["name"],
                "tag_number" => $index["tag_number"],
            ]);
        }

        return GlobalFunction::save(Message::ASSET_SAVE, $asset);
    }
}
