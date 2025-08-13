<?php

namespace App\Http\Requests\JobOrderTransaction;

use App\Models\JobOrder;
use App\Response\Message;
use App\Models\JobOrderMinMax;
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
                $this->route()->job_order_transaction
                    ? "unique:jo_transactions,jo_number," .
                        $this->route()->job_order_transaction
                    : "unique:jo_transactions,jo_number",
            ],
            "one_charging_sync_id" => "exists:one_charging,id,deleted_at,NULL",
            "company_id" => "exists:companies,id,deleted_at,NULL",
            "business_unit_id" => "exists:business_units,id,deleted_at,NULL",
            "department_id" => "exists:departments,id,deleted_at,NULL",
            "department_unit_id" =>
                "exists:department_units,id,deleted_at,NULL",
            "sub_unit_id" => "exists:sub_units,id,deleted_at,NULL",
            "location_id" => "exists:locations,id,deleted_at,NULL",
            "order" => "required|array|min:1",
            "order.*.description" => "required|string",
            "order.*.uom_id" => "required|integer|exists:uoms,id",
            "order.*.quantity" => "required|numeric|min:1",
            "order.*.unit_price" => "required|numeric|min:0",
            "order.*.total_price" => "required|numeric|min:0",
            "order.*.remarks" => "nullable|string",
        ];
    }

    public function withValidator($validator)
    {
        $requestor_deptartment_id = Auth()->user()->department_id;
        $requestor_department_unit_id = Auth()->user()->department_unit_id;
        $requestor_company_id = Auth()->user()->company_id;
        $requestor_business_id = Auth()->user()->business_unit_id;
        $requestor_location_id = Auth()->user()->location_id;
        $requestor_sub_unit_id = Auth()->user()->sub_unit_id;
        $requestor_one_charging_sync_id = Auth()->user()->one_charging_sync_id;

        $validator->after(function ($validator) use (
            $requestor_company_id,
            $requestor_business_id,
            $requestor_deptartment_id,
            $requestor_department_unit_id,
            $requestor_location_id,
            $requestor_sub_unit_id,
            $requestor_one_charging_sync_id
        ) {
            $total_amount = collect($this->input("order"))->sum("total_price");
            $amount_min_max = JobOrderMinMax::first();

            if (!$amount_min_max) {
                return $validator
                    ->errors()
                    ->add("message", Message::NO_MIN_MAX);
            }

            // Check for charging department approvers
            // $charging_approvers = JobOrder::where(
            //     "company_id",
            //     $this->input("company_id")
            // )
            //     ->where("business_unit_id", $this->input("business_unit_id"))
            //     ->where("department_id", $this->input("department_id"))
            //     ->where(
            //         "department_unit_id",
            //         $this->input("department_unit_id")
            //     )
            //     ->where("sub_unit_id", $this->input("sub_unit_id"))
            //     ->where("location_id", $this->input("location_id"))
            //     ->first();

            $charging_approvers = JobOrder::where(
                "one_charging_sync_id",
                $this->input("one_charging_sync_id")
            )->first();

            if ($total_amount >= $amount_min_max->amount_min) {
                // For amounts greater than min_max, only check charging approvers
                if (!$charging_approvers) {
                    $validator
                        ->errors()
                        ->add("message", "No job request approver setup yet.");
                }
            } else {
                // For amounts less than min_max (direct), check both charging and requestor approvers
                // $requestor_approvers = JobOrder::where(
                //     "company_id",
                //     $requestor_company_id
                // )
                //     ->where("business_unit_id", $requestor_business_id)
                //     ->where("department_id", $requestor_deptartment_id)
                //     ->where("department_unit_id", $requestor_department_unit_id)
                //     ->where("sub_unit_id", $requestor_sub_unit_id)
                //     ->where("location_id", $requestor_location_id)
                //     ->first();

                $requestor_approvers = JobOrder::where(
                    "one_charging_sync_id",
                    $requestor_one_charging_sync_id
                )->first();

                if (!$charging_approvers) {
                    $validator
                        ->errors()
                        ->add("message", "No job order direct approvers yet.");
                } elseif (!$requestor_approvers) {
                    $validator
                        ->errors()
                        ->add("message", "No job order approvers yet");
                }
            }
        });
    }
}
