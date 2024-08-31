<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PRViewRequest extends FormRequest
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
                "in:syncing,pending,cancelled,rejected,approved,cancel,pr_approved,to_po,for_approval,for_receiving,for_receiving_cancelled,for_po_pending,for_receiving_user,reports_po,cancelled_po,return_pr,received,report_approved,report_cancelled",
            ],
        ];
    }
}
