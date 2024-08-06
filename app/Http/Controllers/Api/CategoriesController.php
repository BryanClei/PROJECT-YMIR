<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use App\Models\Categories;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Requests\Categories\StoreRequest;
use App\Http\Requests\Categories\UpdateRequest;

class CategoriesController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $categories = Categories::when($status === "inactive", function (
            $query
        ) {
            $query->onlyTrashed();
        })

            ->useFilters()
            ->orderByDesc("updated_at")
            ->dynamicPaginate();

        if ($categories->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        CategoryResource::collection($categories);
        return GlobalFunction::responseFunction(
            Message::CATEGORIES_DISPLAY,
            $categories
        );
    }

    public function store(StoreRequest $request)
    {
        $categories = Categories::create([
            "name" => $request->name,
            "code" => $request->code,
        ]);

        $company_collect = new CategoryResource($categories);

        return GlobalFunction::save(Message::CATEGORIES_SAVE, $company_collect);
    }

    public function update(StoreRequest $request, $id)
    {
        $categories = Categories::find($id);
        $is_exists = Categories::where("id", $id)->get();

        if ($is_exists->isEmpty()) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $categories->update([
            "name" => $request->name,
            "code" => $request->code,
        ]);
        return GlobalFunction::responseFunction(
            Message::CATEGORIES_UPDATE,
            $categories
        );
    }

    public function destroy($id)
    {
        $categories = Categories::where("id", $id)
            ->withTrashed()
            ->get();

        if ($categories->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $categories = Categories::withTrashed()->find($id);
        $is_active = Categories::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $categories->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $categories->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $categories);
    }

    public function import(ImportRequest $request)
    {
        $import = $request->all();

        foreach ($import as $index) {
            $categories = Categories::create([
                "name" => $index["name"],
                "code" => $index["code"],
            ]);
        }

        return GlobalFunction::save(Message::CATEGORIES_SAVE, $import);
    }
}
