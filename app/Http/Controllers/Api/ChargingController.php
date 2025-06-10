<?php

namespace App\Http\Controllers\Api;

use App\Models\Charging;
use App\Response\Message;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Resources\OneCharging\OneChargingResource;

class ChargingController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->status;

        $charging = Charging::with(
            "company",
            "business_unit",
            "department",
            "department_unit",
            "sub_unit",
            "location"
        )
            ->when($status === "inactive", function ($query) {
                $query->onlyTrashed();
            })
            ->dynamicPaginate();

        if ($charging->isEmpty()) {
            return GlobalFunction::notFound(Message::NO_DATA_FOUND);
        }

        OneChargingResource::collection($charging);

        return GlobalFunction::responseFunction(
            Message::CHARGING_DISPLAY,
            $charging
        );
    }

    public function store(Request $request)
    {
        $sync = $request->all();
        $charging = Charging::upsert(
            $sync,
            ["sync_id"],
            [
                "code",
                "name",
                "company_id",
                "company_code",
                "company_name",
                "business_unit_id",
                "business_unit_code",
                "business_unit_name",
                "department_id",
                "department_code",
                "department_name",
                "department_unit_id",
                "department_unit_code",
                "department_unit_name",
                "sub_unit_id",
                "sub_unit_code",
                "sub_unit_name",
                "location_id",
                "location_code",
                "location_name",
                "deleted_at",
            ]
        );

        return GlobalFunction::save(Message::CHRAGING_SAVE, $charging);
    }
}
