<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use Illuminate\Http\Request;
use App\Models\JobOrderMinMax;
use App\Functions\GlobalFunction;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\JobOrderMinMax\StoreRequest;

class JobOrderMinMaxController extends Controller
{
    public function index()
    {
        $min_max = JobOrderMinMax::all();

        return GlobalFunction::responseFunction(
            Message::MIN_MAX_DISPLAY,
            $min_max
        );
    }

    public function show(Request $request, $id)
    {
        $min_max = JobOrderMinMax::where("id", $id)->first();

        return GlobalFunction::responseFunction(
            Message::MIN_MAX_DISPLAY,
            $min_max
        );
    }

    public function store(StoreRequest $request)
    {
        $existingRecord = JobOrderMinMax::first();

        if ($existingRecord) {
            return GlobalFunction::responseFunction(Message::MIN_ERROR);
        }

        $save_amount = JobOrderMinMax::create([
            "amount_min" => $request->amount_min,
        ]);

        return GlobalFunction::save(Message::MIN_SET, $save_amount);
    }

    public function update(StoreRequest $request, $id)
    {
        $current_min_max = JobOrderMinMax::where("id", $id)->first();

        if (!$current_min_max) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $current_min_max->update([
            "amount_min" => $request->amount_min,
        ]);

        $update_amount = JobOrderMinMax::where("id", $id)->first();

        return GlobalFunction::save(Message::MIN_MAX_UPDATE, $update_amount);
    }
}
