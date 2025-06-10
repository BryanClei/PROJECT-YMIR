<?php

namespace App\Http\Requests\PO;

use Illuminate\Foundation\Http\FormRequest;

class PORequest extends FormRequest
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
            "order_item_ids" => "required|array|min:1",
            "order_item_ids.*" => "integer|exists:po_items,id,deleted_at,NULL",
            "reason" => "required|string",
            "no_rr" => "nullable|boolean",
        ];
    }
}
