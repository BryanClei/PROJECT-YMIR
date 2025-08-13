<?php

namespace App\Http\Requests\PurchaseRequest;

use App\Models\Charging;
use App\Models\ApproverSettings;
use Illuminate\Foundation\Http\FormRequest;

class AssetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            "*.pr_number" => [
                $this->route()->pr_transaction
                    ? "unique:pr_transactions,pr_number," .
                        $this->route()->pr_transaction
                    : "unique:pr_transactions,pr_number",
            ],
            // "supplier_id" => "exists:suppliers,id,deleted_at,NULL",
            "*.one_charging_id" => "required",
            "*.one_charging_code" => "required",
            "*.one_charging_name" => "required",
            "*.company_code" => "exists:companies,code,deleted_at,NULL",
            "*.business_unit_code" =>
                "exists:business_units,code,deleted_at,NULL",
            "*.department_code" => "exists:departments,code,deleted_at,NULL",
            "*.department_unit_code" =>
                "exists:department_units,code,deleted_at,NULL",
            "*.sub_unit_code" => "exists:sub_units,code,deleted_at,NULL",
            "*.location_code" => "exists:locations,code,deleted_at,NULL",
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $this->all();

            foreach ($data as $index => $item) {
                if (
                    isset(
                        $item["one_charging_id"],
                        $item["one_charging_code"],
                        $item["one_charging_name"]
                    )
                ) {
                    $exists = Charging::where(
                        "sync_id",
                        $item["one_charging_id"]
                    )
                        ->where("code", $item["one_charging_code"])
                        ->where("name", $item["one_charging_name"])
                        ->whereNull("deleted_at")
                        ->exists();

                    if (!$exists) {
                        $validator
                            ->errors()
                            ->add(
                                "{$index}.one_charging",
                                "The one charging ID, code, and name combination does not match our records."
                            );
                    }
                }
            }
        });
    }

    // public function withValidator($validator)
    // {
    //     $validator->after(function ($validator) {
    //         // $validator->errors()->add("custom", $this->user()->id);
    //         // $validator->errors()->add("custom", $this->route()->id);
    //         // $validator->errors()->add("custom", "STOP!");

    //         $approvers = ApproverSettings::where(
    //             "business_unit_id",
    //             $this->input("*.business_unit_id")
    //         )
    //             ->where("company_id", $this->input("*.company_id"))
    //             ->where("department_id", $this->input("*.department_id"))
    //             ->where(
    //                 "department_unit_id",
    //                 $this->input("*.department_unit_id")
    //             )
    //             ->where("sub_unit_id", $this->input("*.sub_unit_id"))
    //             ->where("location_id", $this->input("*.location_id"))
    //             ->get()
    //             ->first();
    //         if (!$approvers) {
    //             $validator->errors()->add("message", "No approvers yet.");
    //         }
    //     });
    // }
}
