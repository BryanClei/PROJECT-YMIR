<?php

namespace App\Http\Requests\Buyer;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePriceRequest extends FormRequest
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
        $user_id = Auth()->user()->id;
        return [
            "po_id" => "required|exists:po_transactions,id,deleted_at,NULL",
            // "edit_remarks" => "required",
            "orders" => "required|array",
            "orders.*.id" => "required|exists:po_orders,id,deleted_at,NULL",
            // "orders.*.id" =>
            //     "required|exists:po_orders,id,deleted_at,NULL,buyer_id," .
            //     $user_id,
        ];
    }
}
