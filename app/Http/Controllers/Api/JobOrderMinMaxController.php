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
        DB::beginTransaction();

        try {
            $save_amount = JobOrderMinMax::create([
                "amount_min" => $request->amount_min,
            ]);

            DB::commit();
            return GlobalFunction::save(Message::MIN_MAX_SET, $save_amount);
        } catch (\Exception $e) {
            DB::rollBack();
            return GlobalFunction::error($e);
        }
    }

    public function update(StoreRequest $request, $id)
    {
        $current_min_max = JobOrderMinMax::where("id", $id)->first();

        if (!$current_min_max) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        DB::beginTransaction();

        try {
            $current_min_max->update([
                "amount_min" => $request->amount_min,
            ]);

            DB::commit();

            $update_amount = JobOrderMinMax::where("id", $id)->first();

            return GlobalFunction::save(
                Message::MIN_MAX_UPDATE,
                $update_amount
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return GlobalFunction::error($e);
        }
    }
}
