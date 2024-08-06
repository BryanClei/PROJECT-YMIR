<?php

namespace App\Http\Requests\Location;

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
            "*.code" => ["required", "unique:locations,code", "distinct"],
            "*.sub_unit.*.sub_unit_id" => [
                "exists:sub_units,name,deleted_at,NULL",
            ],
        ];
    }

    public function attributes()
    {
        return [
            "*.code" => "code",
            "*.sub_unit.*.sub_unit_id" => "sub unit",
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
