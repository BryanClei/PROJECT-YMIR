<?php

namespace App\Http\Requests\JoPo;

use App\Models\JobOrderPurchaseOrder;
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
                "required",
                "exists:jo_transactions,jo_number,deleted_at,NULL",
            ],
            "one_charging_sync_id" => [
                "required",
                "exists:one_charging,sync_id,deleted_at,NULL",
            ],
            "company_id" => "exists:companies,id,deleted_at,NULL",
            "business_unit_id" => "exists:business_units,id,deleted_at,NULL",
            "department_id" => "exists:departments,id,deleted_at,NULL",
            "department_unit_id" =>
                "exists:department_units,id,deleted_at,NULL",
            "sub_unit_id" => "exists:sub_units,id,deleted_at,NULL",
            "location_id" => "exists:locations,id,deleted_at,NULL",
            // Add validation for `order` array
            "order" => "required|array|min:1",
            "order.*.description" => "required|string",
            "order.*.uom_id" => "required|integer|exists:uoms,id",
            "order.*.quantity" => "required|numeric|min:1",
            "order.*.price" => "required|numeric|min:0",
            "order.*.total_price" => "required|numeric|min:0",
            "order.*.remarks" => "nullable|string",
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $total_amount = collect($this->input("order"))->sum("total_price");

            // if ($total_amount >= 150000) {
            //     if (
            //         $this->input("outside_labor") === false &&
            //         $this->input("cap_ex") === false
            //     ) {
            //         $validator
            //             ->errors()
            //             ->add(
            //                 "cap_ex",
            //                 "Cap Ex is required for orders with total amount 150,000. when outside labor is uncheck."
            //             );
            //     }
            // }

            // $charging_po_approvers = JobOrderPurchaseOrder::where(
            //     "company_id",
            //     $this->input("company_id")
            // )
            //     ->where("business_unit_id", $this->input("business_unit_id"))
            //     ->where("department_id", $this->input("department_id"))
            //     ->first();

            $charging_po_approvers = JobOrderPurchaseOrder::where(
                "one_charging_sync_id",
                $this->input("one_charging_sync_id")
            )->first();

            if (!$charging_po_approvers) {
                $validator->errors()->add("message", "No po approvers yet.");
            }
        });
    }
}
