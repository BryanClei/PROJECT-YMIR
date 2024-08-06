<?php

namespace App\Http\Requests\AssetVladimir;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            "rr_number" => "required|array",
            "rr_number.*" => ["exists:rr_orders,rr_number"],
        ];
    }
}
