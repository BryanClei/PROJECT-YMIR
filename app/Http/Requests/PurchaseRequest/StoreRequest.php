<?php

namespace App\Http\Requests\PurchaseRequest;

use Carbon\Carbon;
use App\Models\ApproverSettings;
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
        $rules = [
            "pr_number" => [
                $this->route()->pr_transaction
                    ? "unique:pr_transactions,pr_number," .
                        $this->route()->pr_transaction
                    : "unique:pr_transactions,pr_number",
            ],
            "company_id" => "exists:companies,id,deleted_at,NULL",
            "business_unit_id" => "exists:business_units,id,deleted_at,NULL",
            "department_id" => "exists:departments,id,deleted_at,NULL",
            "department_unit_id" =>
                "exists:department_units,id,deleted_at,NULL",
            "sub_unit_id" => "exists:sub_units,id,deleted_at,NULL",
            "location_id" => "exists:locations,id,deleted_at,NULL",
            "ship_to" => "required",
        ];

        if ($this->boolean("for_po_only") || $this->filled("supplier_id")) {
            $rules["order.*.unit_price"] = "required|numeric|gt:0";
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $shouldValidateUnitPrices =
                $this->boolean("for_po_only") || $this->filled("supplier_id");

            if ($shouldValidateUnitPrices) {
                $orders = $this->input("order", []);

                foreach ($orders as $index => $item) {
                    $unitPrice = $item["unit_price"] ?? null;

                    if (!is_numeric($unitPrice) || $unitPrice <= 0) {
                        $validator
                            ->errors()
                            ->add(
                                "order.{$index}.unit_price",
                                "The unit price must be a number greater than zero."
                            );
                    }
                }
            }

            // Approver check (unchanged)
            $approvers = \App\Models\ApproverSettings::where(
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
                ->first();

            if (!$approvers) {
                $validator->errors()->add("message", "No approvers yet.");
            }
        });
    }
}
