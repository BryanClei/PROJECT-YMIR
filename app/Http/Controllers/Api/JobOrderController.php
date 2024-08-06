<?php

namespace App\Http\Controllers\Api;

use App\Models\JobOrder;
use App\Response\Message;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Models\JobOrderApprovers;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Http\Requests\JobOrder\StoreRequest;

class JobOrderController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;
        $job_order = JobOrder::when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })
            ->with(
                "company",
                "business_unit",
                "department",
                "department_unit",
                "sub_unit",
                "locations",
                "set_approver"
            )
            ->useFilters()
            ->latest("updated_at")
            ->dynamicPaginate();

        $is_empty = $job_order->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::APPROVERS_DISPLAY,
            $job_order
        );
    }

    public function store(StoreRequest $request)
    {
        $job_order = new JobOrder([
            "module" => "Job Order",
            "company_id" => $request["company_id"],
            "business_unit_id" => $request["business_unit_id"],
            "department_id" => $request["department_id"],
            "department_unit_id" => $request["department_unit_id"],
            "sub_unit_id" => $request["sub_unit_id"],
            "location_id" => $request["location_id"],
        ]);

        $job_order->save();

        $set_approver = $request["settings_approver"];

        foreach ($set_approver as $key => $value) {
            JobOrderApprovers::create([
                "job_order_id" => $job_order->id,
                "approver_id" => $set_approver[$key]["approver_id"],
                "approver_name" => $set_approver[$key]["approver_name"],
                "layer" => $set_approver[$key]["layer"],
            ]);
        }

        return GlobalFunction::save(Message::APPROVERS_SAVE, $job_order);
    }

    public function update(StoreRequest $request, $id)
    {
        $setting = JobOrder::find($id);

        $set_approver = $request["settings_approver"];

        // TAG SETTINGS
        $newTaggedApproval = collect($set_approver)
            ->pluck("id")
            ->toArray();
        $currentTaggedApproval = JobOrderApprovers::where("job_order_id", $id)
            ->get()
            ->pluck("id")
            ->toArray();

        foreach ($currentTaggedApproval as $set_approver_id) {
            if (!in_array($set_approver_id, $newTaggedApproval)) {
                JobOrderApprovers::where("id", $set_approver_id)->forceDelete();
            }
        }

        foreach ($set_approver as $index => $value) {
            JobOrderApprovers::updateOrCreate(
                [
                    "id" => $value["id"] ?? null,
                    "job_order_id" => $id,
                ],
                [
                    "approver_id" => $value["approver_id"],
                    "approver_name" => $value["approver_name"],
                    "layer" => $value["layer"],
                ]
            );
        }

        $setting->update([
            "company_id" => $request["company_id"],
            "business_unit_id" => $request["business_unit_id"],
            "department_id" => $request["department_id"],
            "department_unit_id" => $request["department_unit_id"],
            "sub_unit_id" => $request["sub_unit_id"],
            "location_id" => $request["location_id"],
        ]);

        return GlobalFunction::responseFunction(
            Message::APPROVERS_UPDATE,
            $setting
        );
    }

    public function destroy($id)
    {
        $job_order = JobOrder::where("id", $id)
            ->withTrashed()
            ->get();

        if ($job_order->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $job_order = JobOrder::withTrashed()->find($id);
        $is_active = JobOrder::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $job_order->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $job_order->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $job_order);
    }
}
