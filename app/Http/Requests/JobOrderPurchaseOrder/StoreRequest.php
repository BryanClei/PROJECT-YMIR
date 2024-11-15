<?php

namespace App\Http\Requests\JobOrderPurchaseOrder;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
        $company_id = $this->input("company_id");
        $business_unit_id = $this->input("business_unit_id");
        $department_id = $this->input("department_id");
        return [
            "company_id" => [
                "required",
                "exists:companies,id,deleted_at,NULL",
                Rule::unique("job_order_purchase_order", "company_id")
                    ->ignore($this->route("job_order_purchase_order"))
                    ->where("business_unit_id", $business_unit_id)
                    ->where("department_id", $department_id),
            ],
            "business_unit_id" => [
                "required",
                "exists:business_units,id,deleted_at,NULL",
                Rule::unique("job_order_purchase_order", "business_unit_id")
                    ->ignore($this->route("job_order_purchase_order"))
                    ->where("company_id", $company_id)
                    ->where("department_id", $department_id),
            ],
            "department_id" => [
                "required",
                "exists:departments,id,deleted_at,NULL",
                Rule::unique("job_order_purchase_order", "department_id")
                    ->ignore($this->route("job_order_purchase_order"))
                    ->where("company_id", $company_id)
                    ->where("business_unit_id", $business_unit_id),
            ],
            "settings_approver.*.approver_id" => [
                "required",
                "exists:users,id,deleted_at,NULL",
            ],
            "settings_approver.*.layer" => ["distinct"],
        ];
    }

    public function attributes()
    {
        return [
            "company_id" => "company",
            "business_unit_id" => "business unit",
            "department_id" => "department",
            "settings_approver.*.approver_id" => "approver",
        ];
    }

    public function messages()
    {
        return [
            "unique" => ":attribute already been taken.",
            "settings_approver.*.approver_id.exists" =>
                "This :attribute does not exists.",
        ];
    }
}
