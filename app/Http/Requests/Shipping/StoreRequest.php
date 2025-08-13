<?php

namespace App\Http\Requests\Shipping;

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
    public function rules(): array
    {
        return [
            "location" => [
                "required",
                $this->route()->ship_to
                    ? "unique:shipping,location," . $this->route()->ship_to
                    : "unique:shipping,location",
            ],
            "address" => ["required"],
        ];
    }

    public function messages(): array
    {
        return [
            "required" => "The :attribute is required",
            "unique" => "The :input is already taken.",
        ];
    }
}
