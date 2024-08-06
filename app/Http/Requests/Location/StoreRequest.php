<?php

namespace App\Http\Requests\Location;

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
            "name" => ["required"],
            "code" => [
                "required",
                "string",
                $this->route()->location
                    ? "unique:locations,code," . $this->route()->location
                    : "unique:locations,code",
            ],
            "sub_unit_id" => [
                "required",
                "exists:sub_units,id,deleted_at,NULL",
            ],
        ];
    }
}
