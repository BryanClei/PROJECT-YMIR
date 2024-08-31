<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use App\Models\POSettings;
use App\Models\PoApprovers;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Http\Requests\PO\StoreRequest;
use App\Http\Resources\PoSettingsResource;

class PoApproversController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;
         $po_setting = POSettings::with("set_approver")
            ->when($status === "inactive", function ($query) {
                $query->onlyTrashed();
            })
            ->latest("updated_at")
            ->useFilters()
            ->dynamicPaginate();

        $is_empty = $po_setting->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        PoSettingsResource::collection($po_setting);

        return GlobalFunction::responseFunction(
            Message::APPROVERS_DISPLAY,
            $po_setting
        );
    }
    public function store(StoreRequest $request)
    {
        $approver = new POSettings([
            "module" => "PO APPROVERS",
            "company_id" => $request["company_id"],
            "company_name" => $request["company_name"],
            "business_unit_id" => $request["business_unit_id"],
            "business_unit_name" => $request["business_unit_name"],
            "department_id" => $request["department_id"],
            "department_name" => $request["department_name"],
        ]);

        $approver->save();

        $set_approver = $request["settings_approver"];

        foreach ($set_approver as $key => $value) {
            PoApprovers::create([
                "po_settings_id" => $approver->id,
                "approver_id" => $set_approver[$key]["approver_id"],
                "approver_name" => $set_approver[$key]["approver_name"],
                "price_range" => $set_approver[$key]["price_range"],
                // "to_price" => $set_approver[$key]["to_price"],
                "layer" => $set_approver[$key]["layer"],
            ]);
        }

        return GlobalFunction::save(Message::APPROVERS_SAVE, $approver);
    }

    public function update(StoreRequest $request, $id)
    {
        $setting = POSettings::with("set_approver")->find($id);

        $set_approver = $request["settings_approver"];

        $newTaggedApproval = collect($set_approver)
            ->pluck("id")
            ->toArray();
        $currentTaggedApproval = PoApprovers::where("po_settings_id", $id)
            ->get()
            ->pluck("id")
            ->toArray();

        foreach ($currentTaggedApproval as $set_approver_id) {
            if (!in_array($set_approver_id, $newTaggedApproval)) {
                PoApprovers::where("id", $set_approver_id)->forceDelete();
            }
        }

        foreach ($set_approver as $index => $value) {
            PoApprovers::updateOrCreate(
                [
                    "id" => $value["id"] ?? null,
                    "po_settings_id" => $id,
                    "approver_id" => $value["approver_id"],
                    "approver_name" => $value["approver_name"],
                    "price_range" => $value["price_range"],
                    // "to_price" => $value["to_price"],
                ],
                ["layer" => $value["layer"]]
            );
        }

        $setting->update([
            "company_id" => $request["company_id"],
            "company_name" => $request["company_name"],
            "business_unit_id" => $request["business_unit_id"],
            "business_unit_name" => $request["business_unit_name"],
            "department_id" => $request["department_id"],
            "department_name" => $request["department_name"],
        ]);

        return GlobalFunction::responseFunction(
            Message::APPROVERS_UPDATE,
            $setting
        );
    }
}
