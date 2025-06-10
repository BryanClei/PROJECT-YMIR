<?php

namespace App\Http\Requests\RRDateSetup;

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
            "setup_name" => ["required", "integer"],
            "previous_days" => ["required", "string"],
            "forward_days" => ["required", "string"],
            "previous_month" => ["required", "string"],
            "threshold_days" => ["required", "string"],
        ];
    }

    public function attributes(): array
    {
        return [
            "setup_name" => "setup name",
            "previous_days" => "previous days",
            "forward_days" => "forward days",
            "previous_month" => "previous month",
            "threshold days" => "threshold days",
        ];
    }

    public function messages(): array
    {
        return [
            "required" => "The :attribute field is required.",
            "string" => "The :attribute must be string.",
        ];
    }
}
