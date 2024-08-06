<?php

namespace App\Http\Requests\ReceivedReceipt;

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
            "pr_no" => [
                "required",
                "exists:pr_transactions,id,deleted_at,NULL",
            ],
            "po_no" => [
                "required",
                "exists:po_transactions,id,deleted_at,NULL",
            ],
            "order" => [
                "tagging_id" => "required",
                "delivery_date" => "required",
                "rr_date" => "required",
            ],
        ];
    }
}
