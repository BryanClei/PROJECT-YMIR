<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Charging;
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
                "company_code" => $request["company_code"],
                "company_name" => $request["company_name"],
                "business_unit_id" => $request["business_unit_id"],
                "business_unit_code" => $request["business_unit_code"],
                "business_unit_name" => $request["business_unit_name"],
                "department_id" => $request["department_id"],
                "department_code" => $request["business_unit_code"],
                "department_name" => $request["department_name"],
                "one_charging_id" => $request["one_charging_id"],
                "one_charging_sync_id" => $request["one_charging_sync_id"],
                "one_charging_code" => $request["one_charging_code"],
                "one_charging_name" => $request["one_charging_name"],
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
                "company_code" => $request["company_code"],
                "company_name" => $request["company_name"],
                "business_unit_id" => $request["business_unit_id"],
                "business_unit_code" => $request["business_unit_code"],
                "business_unit_name" => $request["business_unit_name"],
                "department_id" => $request["department_id"],
                "department_code" => $request["business_unit_code"],
                "department_name" => $request["department_name"],
                "one_charging_id" => $request["one_charging_id"],
                "one_charging_sync_id" => $request["one_charging_sync_id"],
                "one_charging_code" => $request["one_charging_code"],
                "one_charging_name" => $request["one_charging_name"],
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

    public function import(Request $request)
    {
        if (!$request->hasFile("file")) {
            return response()->json(["error" => "File is required."], 400);
        }

        $file = $request->file("file");

        if ($file->getClientOriginalExtension() !== "csv") {
            return response()->json(
                ["error" => "Only CSV files are allowed."],
                422
            );
        }

        $path = $file->getRealPath();

        if (($handle = fopen($path, "r")) === false) {
            return response()->json(
                ["error" => "Unable to open the file."],
                500
            );
        }

        $header = fgetcsv($handle);

        //trip BOM from the first header column (only once)
        if (!empty($header) && isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', "", $header[0]);
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $missingSettings = [];

        while (($data = fgetcsv($handle)) !== false) {
            $row = array_combine($header, $data);
            $row = array_change_key_case($row, CASE_LOWER);

            [$firstName, $lastName] = $this->splitName($row["approver"]);

            // Look up full charging entry in a single query
            $charging = Charging::where("code", $row["code"])->first();

            // If no matching charging entry found, skip
            if (!$charging) {
                if (
                    !empty(trim($row["approver"])) ||
                    !empty(trim($row["name"]))
                ) {
                    $missingSettings[] = [
                        "approver" => trim($row["approver"]),
                        "module" => trim($row["name"]),
                    ];
                }
                continue;
            }

            // Build the approver setting data
            $settingData = [
                "module" => $row["name"],

                "company_id" => $charging->company_id,
                "company_code" => $charging->company_code,
                "company_name" => $charging->company_name,

                "business_unit_id" => $charging->business_unit_id,
                "business_unit_code" => $charging->business_unit_code,
                "business_unit_name" => $charging->business_unit_name,

                "department_id" => $charging->department_id,
                "department_code" => $charging->department_code,
                "department_name" => $charging->department_name,

                "one_charging_id" => $charging->id,
                "one_charging_sync_id" => $charging->sync_id,
                "one_charging_code" => $charging->code,
                "one_charging_name" => $charging->name,

                // 'department_unit_id'      => $charging->department_unit_id,
                // 'department_unit_code'    => $charging->department_unit_code,

                // 'sub_unit_id'             => $charging->sub_unit_id,
                // 'sub_unit_code'           => $charging->sub_unit_code,

                // 'location_id'             => $charging->location_id,
                // 'location_code'           => $charging->location_code,
            ];

            $setting = JobOrderPurchaseOrder::firstOrCreate($settingData);

            // Match user
            $user = User::where("first_name", $firstName)
                ->where("last_name", $lastName)
                ->first();

            $user_id = $user->id;

            // Check if approver already exists
            $existing = JobOrderPurchaseOrderApprovers::where(
                "approver_name",
                $row["approver"]
            )
                ->where("approver_id", $user_id)
                ->where("layer", $row["layer"])
                ->where("jo_purchase_order_id", $setting->id)
                ->first();

            if ($existing) {
                if ($existing->user_id !== $user_id) {
                    $existing->update([
                        "approver_id" => $user_id,
                    ]);
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                JobOrderPurchaseOrderApprovers::create([
                    "approver_name" => $row["approver"],
                    "layer" => $row["layer"],
                    "jo_purchase_order_id" => $setting->id,
                    "approver_id" => $user_id,
                    "base_price" => "0",
                ]);
                $imported++;
            }
        }

        fclose($handle);

        return response()->json([
            "message" => "Jo Approver import completed.",
            "created" => $imported,
            "updated" => $updated,
            "skipped" => $skipped,
            "missing_settings" => collect($missingSettings)
                ->unique()
                ->values()
                ->all(),
        ]);
    }

    private function splitName($fullName)
    {
        $fullName = trim(preg_replace("/\s+/", " ", $fullName)); // normalize spaces
        $parts = explode(" ", $fullName);

        $last = array_pop($parts); // last name = last word
        $first = implode(" ", $parts); // first name = everything before

        return [$first, $last];
    }
}
