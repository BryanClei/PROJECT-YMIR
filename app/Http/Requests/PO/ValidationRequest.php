<?php

namespace App\Http\Requests\PO;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ValidationRequest extends FormRequest
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
            "one_charging_sync_id" => [
                "required",
                "exists:one_charging,id,deleted_at,NULL",
            ],
            "company_id" => ["required", "exists:companies,id,deleted_at,NULL"],
            "company_code" => ["required"],
            "business_unit_id" => [
                "required",
                "exists:business_units,id,deleted_at,NULL",
            ],
            "business_unit_code" => ["required"],
            "department_id" => [
                "required",
                "exists:departments,id,deleted_at,NULL",
            ],
            "department_unit_code" => ["required"],
            "location_code" => ["required"],
            "sub_unit_code" => ["required"],
            "order.*.pr_item_id" => [
                "required",
                Rule::exists("pr_items", "id")
                    ->whereNull("po_at")
                    ->where("deleted_at", null),
            ],
            "order" => ["required", "array"],
            "order.*.price" => ["required", "numeric", "min:0.01"],
            // "order.*.total_price" => ["required", "numeric", "min:0.01"],
        ];
    }

    public function messages()
    {
        return [
            "order.*.pr_item_id.exists" =>
                "The selected item already has a PO.",
            "orders.*.price.min" => "The items price must be at least 0.01.",
        ];
    }
}
