<?php

namespace App\Http\Requests\ReceivedReceipt;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequestV2 extends FormRequest
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
            "order.*.pr_no" => ["required", "exists:pr_transactions,id"],
            "order.*.po_no" => ["required", "exists:po_transactions,id"],
            "order.*.item_id" => ["required", "exists:po_orders,id"],
            "order.*.delivery_date" => ["required"],
            "order.*.rr_date" => ["required"],
        ];
    }

    public function messages()
    {
        return [
            "order.*.pr_no.exists" => "The selected PR number does not exist.",
            "order.*.po_no.exists" => "The selected PO number does not exist.",
            "order.*.item_id.exists" => "The Item ID does not exists.",
            "order.*.delivery_date.required" =>
                "Delivery date is required for each order.",
            "order.*.rr_date.required" => "RR date is required for each order.",
            "order.*.delivery_date.date" =>
                "Delivery date must be a valid date.",
            "order.*.rr_date.date" => "RR date must be a valid date.",
        ];
    }
}
