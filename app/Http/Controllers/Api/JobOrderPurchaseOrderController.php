<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Models\JobOrderPurchaseOrder;
use App\Http\Resources\PoSettingsResource;
use App\Http\Resources\JoPoSettingsResource;
use App\Models\JobOrderPurchaseOrderApprovers;
use App\Http\Requests\JobOrderPurchaseOrder\StoreRequest;

class JobOrderPurchaseOrderController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;
        $po_setting = JobOrderPurchaseOrder::with("set_approver")
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

        JoPoSettingsResource::collection($po_setting);

        return GlobalFunction::responseFunction(
            Message::APPROVERS_DISPLAY,
            $po_setting
        );
    }

    public function show()
    {
    }

    public function store(StoreRequest $request)
    {
        DB::beginTransaction();

        try {
            $approver = new JobOrderPurchaseOrder([
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
                JobOrderPurchaseOrderApprovers::create([
                    "jo_purchase_order_id" => $approver->id,
                    "approver_id" => $set_approver[$key]["approver_id"],
                    "approver_name" => $set_approver[$key]["approver_name"],
                    "base_price" => $set_approver[$key]["base_price"],
                    "layer" => $set_approver[$key]["layer"],
                ]);
            }

            DB::commit();

            return GlobalFunction::save(Message::APPROVERS_SAVE, $approver);
        } catch (\Exception $e) {
            DB::rollBack();
            return GlobalFunction::error($e);
        }
    }

    public function update(StoreRequest $request, $id)
    {
        $setting = JobOrderPurchaseOrder::with("set_approver")->find($id);

        $set_approver = $request["settings_approver"];

        $newTaggedApproval = collect($set_approver)
            ->pluck("id")
            ->toArray();
        $currentTaggedApproval = JobOrderPurchaseOrderApprovers::where(
            "jo_purchase_order_id",
            $id
        )
            ->get()
            ->pluck("id")
            ->toArray();

        DB::beginTransaction();

        try {
            foreach ($currentTaggedApproval as $set_approver_id) {
                if (!in_array($set_approver_id, $newTaggedApproval)) {
                    JobOrderPurchaseOrderApprovers::where(
                        "id",
                        $set_approver_id
                    )->forceDelete();
                }
            }

            foreach ($set_approver as $index => $value) {
                JobOrderPurchaseOrderApprovers::updateOrCreate(
                    [
                        "id" => $value["id"] ?? null,
                        "jo_purchase_order_id" => $id,
                        "approver_id" => $value["approver_id"],
                        "approver_name" => $value["approver_name"],
                        "base_price" => $value["base_price"],
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

            DB::commit();

            // Fetch the latest updated record with relationships
            $updatedSetting = JobOrderPurchaseOrder::with("set_approver")
                ->where("id", $id)
                ->latest("updated_at")
                ->first();

            return GlobalFunction::responseFunction(
                Message::APPROVERS_UPDATE,
                $updatedSetting
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return GlobalFunction::error($e);
        }
    }

    public function destroy($id)
    {
        $job_order_purchase_order = JobOrderPurchaseOrder::where("id", $id)
            ->withTrashed()
            ->get();

        if ($job_order_purchase_order->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $job_order_purchase_order = JobOrderPurchaseOrder::withTrashed()->find(
            $id
        );
        $is_active = JobOrderPurchaseOrder::withTrashed()
            ->where("id", $id)
            ->first();

        DB::beginTransaction();

        try {
            if (!$is_active) {
                return $is_active;
            } elseif (!$is_active->deleted_at) {
                $job_order_purchase_order->delete();
                $message = Message::ARCHIVE_STATUS;
            } else {
                $job_order_purchase_order->restore();
                $message = Message::RESTORE_STATUS;
            }

            DB::commit();
            return GlobalFunction::responseFunction(
                $message,
                $job_order_purchase_order
            );
        } catch (\Exception $e) {
            DB::rollback();
            return GlobalFunction::error($e);
        }
    }
}
