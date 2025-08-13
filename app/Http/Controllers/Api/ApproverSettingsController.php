<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Charging;
use App\Response\Message;
use App\Models\SetApprover;
use Illuminate\Http\Request;
use App\Models\ApproverSettings;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Http\Requests\ApproverSettings\StoreRequest;

class ApproverSettingsController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;
        $approver = ApproverSettings::when($status === "inactive", function (
            $query
        ) {
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

        $is_empty = $approver->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::APPROVERS_DISPLAY,
            $approver
        );
    }

    public function store(StoreRequest $request)
    {
        $approver = new ApproverSettings([
            "module" => $request["module"],
            "company_id" => $request["company_id"],
            "company_code" => $request["company_code"],
            "business_unit_id" => $request["business_unit_id"],
            "business_unit_code" => $request["business_unit_code"],
            "department_id" => $request["department_id"],
            "department_code" => $request["department_code"],
            "department_unit_id" => $request["department_unit_id"],
            "department_unit_code" => $request["department_unit_code"],
            "sub_unit_id" => $request["sub_unit_id"],
            "sub_unit_code" => $request["sub_unit_code"],
            "location_id" => $request["location_id"],
            "location_code" => $request["location_code"],
            "one_charging_id" => $request["one_charging_id"],
            "one_charging_sync_id" => $request["one_charging_sync_id"],
            "one_charging_code" => $request["one_charging_code"],
            "one_charging_name" => $request["one_charging_name"],
        ]);

        $approver->save();

        $set_approver = $request["settings_approver"];

        foreach ($set_approver as $key => $value) {
            SetApprover::create([
                "approver_settings_id" => $approver->id,
                "approver_id" => $set_approver[$key]["approver_id"],
                "approver_name" => $set_approver[$key]["approver_name"],
                "layer" => $set_approver[$key]["layer"],
            ]);
        }

        return GlobalFunction::save(Message::APPROVERS_SAVE, $approver);
    }
    public function update(StoreRequest $request, $id)
    {
        $setting = ApproverSettings::find($id);

        $set_approver = $request["settings_approver"];

        // TAG SETTINGS
        $newTaggedApproval = collect($set_approver)
            ->pluck("id")
            ->toArray();
        $currentTaggedApproval = SetApprover::where("approver_settings_id", $id)
            ->get()
            ->pluck("id")
            ->toArray();

        foreach ($currentTaggedApproval as $set_approver_id) {
            if (!in_array($set_approver_id, $newTaggedApproval)) {
                SetApprover::where("id", $set_approver_id)->forceDelete();
            }
        }

        foreach ($set_approver as $index => $value) {
            SetApprover::updateOrCreate(
                [
                    "id" => $value["id"] ?? null,
                    "approver_settings_id" => $id,
                    "approver_id" => $value["approver_id"],
                    "approver_name" => $value["approver_name"],
                ],
                ["layer" => $value["layer"]]
            );
        }

        $setting->update([
            "company_id" => $request["company_id"],
            "company_code" => $request["company_code"],
            "business_unit_id" => $request["business_unit_id"],
            "business_unit_code" => $request["business_unit_code"],
            "department_id" => $request["department_id"],
            "department_code" => $request["department_code"],
            "department_unit_id" => $request["department_unit_id"],
            "department_unit_code" => $request["department_unit_code"],
            "sub_unit_id" => $request["sub_unit_id"],
            "sub_unit_code" => $request["sub_unit_code"],
            "location_id" => $request["location_id"],
            "location_code" => $request["location_code"],
            "one_charging_id" => $request["one_charging_id"],
            "one_charging_sync_id" => $request["one_charging_sync_id"],
            "one_charging_code" => $request["one_charging_code"],
            "one_charging_name" => $request["one_charging_name"],
        ]);

        return GlobalFunction::responseFunction(
            Message::APPROVERS_UPDATE,
            $setting
        );
    }

    public function destroy($id)
    {
        $approver = ApproverSettings::where("id", $id)
            ->withTrashed()
            ->get();

        if ($approver->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $approver = ApproverSettings::withTrashed()->find($id);
        $is_active = ApproverSettings::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $approver->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $approver->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $approver);
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
                $missingSettings[] = $row["name"];
                continue;
            }

            // Build the approver setting data
            $settingData = [
                "module" => $row["name"],

                "company_id" => $charging->company_id,
                "company_code" => $charging->company_code,

                "business_unit_id" => $charging->business_unit_id,
                "business_unit_code" => $charging->business_unit_code,

                "department_id" => $charging->department_id,
                "department_code" => $charging->department_code,

                "department_unit_id" => $charging->department_unit_id,
                "department_unit_code" => $charging->department_unit_code,

                "sub_unit_id" => $charging->sub_unit_id,
                "sub_unit_code" => $charging->sub_unit_code,

                "location_id" => $charging->location_id,
                "location_code" => $charging->location_code,

                "one_charging_id" => $charging->id,
                "one_charging_sync_id" => $charging->sync_id,
                "one_charging_code" => $charging->code,
                "one_charging_name" => $charging->name,
            ];

            $setting = ApproverSettings::firstOrCreate($settingData);

            // Match user
            $user = User::where("first_name", $firstName)
                ->where("last_name", $lastName)
                ->first();

            $user_id = $user->id;

            // Check if approver already exists
            $existing = SetApprover::where("approver_name", $row["approver"])
                ->where("approver_id", $user_id)
                ->where("layer", $row["layer"])
                ->where("approver_settings_id", $setting->id)
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
                SetApprover::create([
                    "approver_name" => $row["approver"],
                    "layer" => $row["layer"],
                    "approver_settings_id" => $setting->id,
                    "approver_id" => $user_id,
                ]);
                $imported++;
            }
        }

        fclose($handle);

        return response()->json([
            "message" => "Pr Approver import completed.",
            "created" => $imported,
            "updated" => $updated,
            "skipped" => $skipped,
            "missing_settings" => $missingSettings,
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
