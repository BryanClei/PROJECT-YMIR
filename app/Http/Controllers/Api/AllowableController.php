<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Models\AllowablePercentage;
use App\Http\Controllers\Controller;
use App\Http\Resources\AllowablePercentageResource;
use App\Http\Requests\AllowablePercentage\StoreRequest;

class AllowableController extends Controller
{
    public function index()
    {
        $allowablePercentages = AllowablePercentage::all();

        $allowable_collect = AllowablePercentageResource::collection(
            $allowablePercentages
        );

        return GlobalFunction::responseFunction(
            Message::DISPLAY_ALLOWABLE,
            $allowable_collect
        );
    }

    public function store(StoreRequest $request)
    {
        $value = $request->value;

        $allowable = AllowablePercentage::create(["value" => $value]);

        $allowable_collect = new AllowablePercentageResource($allowable);

        return GlobalFunction::save(
            Message::ALLOWABLE_SAVE,
            $allowable_collect
        );
    }

    public function update(Request $request, $id)
    {
        $value = $request->value;

        $allowable = AllowablePercentage::find($id);

        $allowable->update(["value" => $value]);

        $allowable_collect = new AllowablePercentageResource($allowable);

        return GlobalFunction::responseFunction(
            Message::ALLOWABLE_UPDATE,
            $allowable_collect
        );
    }
}
