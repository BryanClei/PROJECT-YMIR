<?php

namespace App\Http\Requests\JobOrder;

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
        $department_unit_id = $this->input("department_unit_id");
        $sub_unit_id = $this->input("sub_unit_id");
        $location_id = $this->input("location_id");
        return [
            "company_id" => [
                "required",
                "exists:companies,id,deleted_at,NULL",
                Rule::unique("job_order", "company_id")
                    ->ignore($this->route("job_order"))
                    ->where("location_id", $location_id)
                    ->where("business_unit_id", $business_unit_id)
                    ->where("department_id", $department_id)
                    ->where("department_unit_id", $department_unit_id)
                    ->where("sub_unit_id", $sub_unit_id),
            ],
            "business_unit_id" => [
                "required",
                "exists:business_units,id,deleted_at,NULL",
                Rule::unique("job_order", "business_unit_id")
                    ->ignore($this->route("job_order"))
                    ->where("company_id", $company_id)
                    ->where("location_id", $location_id)
                    ->where("department_id", $department_id)
                    ->where("department_unit_id", $department_unit_id)
                    ->where("sub_unit_id", $sub_unit_id),
            ],
            "department_id" => [
                "required",
                "exists:departments,id,deleted_at,NULL",
                Rule::unique("job_order", "department_id")
                    ->ignore($this->route("job_order"))
                    ->where("company_id", $company_id)
                    ->where("business_unit_id", $business_unit_id)
                    ->where("location_id", $location_id)
                    ->where("department_unit_id", $department_unit_id)
                    ->where("sub_unit_id", $sub_unit_id),
            ],
            "department_unit_id" => [
                "required",
                "exists:department_units,id,deleted_at,NULL",
                Rule::unique("job_order", "department_unit_id")
                    ->ignore($this->route("job_order"))
                    ->where("company_id", $company_id)
                    ->where("location_id", $location_id)
                    ->where("department_id", $department_id)
                    ->where("department_unit_id", $department_unit_id)
                    ->where("sub_unit_id", $sub_unit_id),
            ],

            "sub_unit_id" => [
                "required",
                "exists:sub_units,id,deleted_at,NULL",
                Rule::unique("job_order", "sub_unit_id")
                    ->ignore($this->route("job_order"))
                    ->where("company_id", $company_id)
                    ->where("business_unit_id", $business_unit_id)
                    ->where("department_id", $department_id)
                    ->where("department_unit_id", $department_unit_id)
                    ->where("location_id", $location_id),
            ],
            "location_id" => [
                "required",
                "exists:locations,id,deleted_at,NULL",
                Rule::unique("job_order", "location_id")
                    ->ignore($this->route("job_order"))
                    ->where("company_id", $company_id)
                    ->where("business_unit_id", $business_unit_id)
                    ->where("department_id", $department_id)
                    ->where("department_unit_id", $department_unit_id)
                    ->where("sub_unit_id", $sub_unit_id),
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
            "department_unit_id" => "department unit",
            "sub_unit_id" => "sub unit",
            "location_id" => "location",
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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // $validator
            //     ->errors()
            //     ->add("custom", $this->route("job_order"));
            // $validator->errors()->add("custom", $this->user()->id);
            // $validator->errors()->add("custom", $this->input("order.*.id"));
        });
    }
}
