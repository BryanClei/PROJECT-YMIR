<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Charging;
use App\Response\Message;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
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
            ->useFilters()
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

        return GlobalFunction::save(Message::CHARGING_SAVE, $charging);
    }

    public function sync()
    {
        $url = "https://api-one.rdfmis.com/api/charging_api?pagination=none";
        $apiKey = "hello world!";

        $response = Http::withHeaders([
            "API_KEY" => $apiKey,
        ])->get($url);

        if ($response->failed()) {
            return response()->json(
                ["message" => "Failed to fetch charging data"],
                500
            );
        }

        $data = $response->json("data");

        $sync = collect($data)
            ->map(function ($charging) {
                return [
                    "sync_id" => $charging["id"],
                    "code" => $charging["code"],
                    "name" => $charging["name"],
                    "company_id" => $charging["company_id"],
                    "company_code" => $charging["company_code"],
                    "company_name" => $charging["company_name"],
                    "business_unit_id" => $charging["business_unit_id"],
                    "business_unit_code" => $charging["business_unit_code"],
                    "business_unit_name" => $charging["business_unit_name"],
                    "department_id" => $charging["department_id"],
                    "department_code" => $charging["department_code"],
                    "department_name" => $charging["department_name"],
                    "department_unit_id" => $charging["unit_id"],
                    "department_unit_code" => $charging["unit_code"],
                    "department_unit_name" => $charging["unit_name"],
                    "sub_unit_id" => $charging["sub_unit_id"],
                    "sub_unit_code" => $charging["sub_unit_code"],
                    "sub_unit_name" => $charging["sub_unit_name"],
                    "location_id" => $charging["location_id"],
                    "location_code" => $charging["location_code"],
                    "location_name" => $charging["location_name"],
                    "deleted_at" => $charging["deleted_at"]
                        ? Carbon::parse($charging["deleted_at"])->format(
                            "Y-m-d H:i:s"
                        )
                        : null,
                ];
            })
            ->toArray();

        Charging::upsert(
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

        return GlobalFunction::save(Message::CHARGING_SAVE, $sync);
    }
}
