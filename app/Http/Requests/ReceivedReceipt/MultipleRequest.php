<?php

namespace App\Http\Requests\ReceivedReceipt;

use Illuminate\Foundation\Http\FormRequest;

class MultipleRequest extends FormRequest
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
            "rr_order" => "required|array",
            "rr_order.*.jo_po_id" => "required|exists:jo_po_transactions,id",
            "rr_order.*.jo_item_id" => "required|exists:jo_po_orders,id",
            "rr_order.*.quantity_serve" => "required|numeric|min:0",
        ];
    }
}
