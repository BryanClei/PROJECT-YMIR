<?php

namespace App\Http\Requests\JoRROrder;

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
            "jo_po" => "required|exists:jo_po_transactions,id,deleted_at,NULL",
            "jo_id" => "required|exists:jo_transactions,id,deleted_at,NULL",
            "rr_order.*.jo_item_id" => [
                "required",
                "exists:jo_po_orders,id,deleted_at,NULL",
            ],
        ];
    }
}
