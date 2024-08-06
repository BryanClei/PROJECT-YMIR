<?php

namespace App\Http\Requests\JobOrderTransaction;

use App\Models\JobOrder;
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
        return [
            "jo_number" => [
                $this->route()->job_order_transaction
                    ? "unique:jo_transactions,jo_number," .
                        $this->route()->job_order_transaction
                    : "unique:jo_transactions,jo_number",
            ],
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // $validator->errors()->add("custom", $this->user()->id);
            // $validator->errors()->add("custom", $this->route()->id);
            // $validator->errors()->add("custom", "STOP!");

            $approvers = JobOrder::where(
                "business_unit_id",
                $this->input("business_unit_id")
            )
                ->where("company_id", $this->input("company_id"))
                ->where("department_id", $this->input("department_id"))
                ->where(
                    "department_unit_id",
                    $this->input("department_unit_id")
                )
                ->where("sub_unit_id", $this->input("sub_unit_id"))
                ->where("location_id", $this->input("location_id"))
                ->get()
                ->first();
            if (!$approvers) {
                $validator->errors()->add("message", "No approvers yet.");
            }
        });
    }
}
