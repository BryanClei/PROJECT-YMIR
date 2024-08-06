<?php

namespace App\Http\Requests\SubUnit;

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
                $this->route()->sub_unit
                    ? "unique:sub_units,code," . $this->route()->sub_unit
                    : "unique:sub_units,code",
            ],
            "department_unit_id" => [
                "required",
                "exists:department_units,id,deleted_at,NULL",
            ],
        ];
    }
}
