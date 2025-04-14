<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportStatusRequest extends FormRequest
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
                "in:rejected,approved,pending,cancelled,admin_reports,view_all,returned,view_all_purchasing_monitoring",
            ],
        ];
    }
}
