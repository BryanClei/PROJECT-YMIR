<?php

namespace App\Http\Requests\SubUnit;

use Illuminate\Foundation\Http\FormRequest;

class ImportRequest extends FormRequest
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
            "*.code" => ["unique:sub_units,code", "distinct"],
            "*.department_unit_id" => [
                "exists:department_units,id,deleted_at,NULL",
            ],
        ];
    }

    public function attributes()
    {
        return [
            "*.code" => "code",
            "*.department_unit_id" => "department unit",
        ];
    }

    public function message()
    {
        return [
            "unique" => ":Attribute is already been taken.",
            "distinct" => ":Attribute has duplicate value.",
            "exists" => ":Attribute is not exists.",
        ];
    }
}
