<?php

namespace App\Http\Controllers\Api;

use App\Models\SubUnit;
use App\Models\Location;
use App\Response\Message;
use Illuminate\Http\Request;
use App\Models\LocationSubUnit;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Http\Resources\LocationResource;
use App\Http\Requests\Location\StoreRequest;
use App\Http\Requests\Location\ImportRequest;

class LocationController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $location = Location::with("sub_units")
            ->when($status === "inactive", function ($query) {
                $query->onlyTrashed();
            })

            ->useFilters()
            ->orderBy("id", "ASC")
            ->dynamicPaginate();

        $is_empty = $location->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }
        LocationResource::collection($location);
        return GlobalFunction::responseFunction(
            Message::LOCATION_DISPLAY,
            $location
        );
    }

    public function store(StoreRequest $request)
    {
        $sub_unit_id = $request->sub_unit_id;
        $location = Location::create([
            "name" => $request->name,
            "code" => $request->code,
        ]);
        $location->sub_units()->attach($request->sub_unit_id);

        $sub_unit_collect = new LocationResource($location);

        return GlobalFunction::save(Message::LOCATION_SAVE, $sub_unit_collect);
    }

    public function update(StoreRequest $request, $id)
    {
        $sub_unit_id = $request->sub_unit_id;
        $location = Location::find($id);
        $is_exists = Location::where("id", $id)->get();

        if ($is_exists->isEmpty()) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $location->update([
            "name" => $request->name,
            "code" => $request->code,
        ]);
        $location->sub_units()->sync($request->sub_unit_id);
        return GlobalFunction::responseFunction(
            Message::LOCATION_UPDATE,
            $location
        );
    }

    public function destroy($id)
    {
        $location = Location::where("id", $id)
            ->withTrashed()
            ->get();

        if ($location->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $location = Location::withTrashed()->find($id);
        $is_active = Location::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $location->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $location->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $location);
    }

    public function import(ImportRequest $request)
    {
        $import = $request->all();

        foreach ($import as $index) {
            $location = Location::create([
                "name" => $index["name"],
                "code" => $index["code"],
            ]);

            $sub_unit = $index["sub_unit"];
            $location_id = $location->id;

            foreach ($sub_unit as $index) {
                $sub_unit_name = $index["sub_unit_id"];
                $sub_unit_id = SubUnit::where("name", $sub_unit_name)->first();

                $location = LocationSubUnit::create([
                    "location_id" => $location_id,
                    "sub_unit_id" => $sub_unit_id->id,
                ]);
            }
        }

        return GlobalFunction::save(Message::LOCATION_SAVE, $import);
    }
}
