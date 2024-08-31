<?php

namespace App\Http\Requests\PO;

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
     * Get the validation rules that apply to the request.t
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            // "supplier_id" => "required|exists:suppliers,id",
            // "company_id" => [
            //     "required",
            //     $this->route()->po_approver
            //         ? "unique:po_settings,company_id," .
            //             $this->route()->po_approver
            //         : "unique:po_settings,company_id",
            // ],
            "company_id" => [
                "required",
                Rule::unique("po_settings")
                    ->where(function ($query) {
                        return $query
                            ->where(
                                "business_unit_id",
                                $this->input("business_unit_id")
                            )
                            ->where(
                                "department_id",
                                $this->input("department_id")
                            );
                    })
                    ->ignore($this->route()->po_approver ?? null),
            ],
            "*approver_id" => ["required", "distinct"],
            "*layer" => ["distinct"],
            "business_unit_id" => [
                "required",
                "exists:business_units,id,deleted_at,NULL",
            ],
            "department_id" => [
                "required",
                "exists:departments,id,deleted_at,NULL",
            ],
        ];
    }

    // public function withValidator($validator)
    // {
    //     $validator->after(function ($validator) {
    //         // $validator->errors()->add("custom", $this->user()->id);
    //         $validator->errors()->add("custom", $this->route()->po_approver);
    //         // $validator->errors()->add("custom", "STOP!");

    //         // $approvers = ApproverSettings::where(
    //         //     "business_unit_id",
    //         //     $this->input("*.business_unit_id")
    //         // )
    //         //     ->where("company_id", $this->input("*.company_id"))
    //         //     ->where("department_id", $this->input("*.department_id"))
    //         //     ->where(
    //         //         "department_unit_id",
    //         //         $this->input("*.department_unit_id")
    //         //     )
    //         //     ->where("sub_unit_id", $this->input("*.sub_unit_id"))
    //         //     ->where("location_id", $this->input("*.location_id"))
    //         //     ->get()
    //         //     ->first();
    //         // if (!$approvers) {
    //         //     $validator->errors()->add("message", "No approvers yet.");
    //         // }

    //         return $validator;
    //     });
    // }
}
