<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JORRTodayDisplay extends FormRequest
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
            "status" => ["required", "string", "in:rr_today"],
            "supplier" => ["required"],
        ];
    }
}
