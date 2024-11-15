<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BDisplay extends FormRequest
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
            "status" => [
                "required",
                "string",
                "in:to_po,rejected,approved,pending,For approval,po_approved,cancelled,s_buyer,s_buyer_tagged,pending_to_receive,completed",
            ],
        ];
    }
}
