<?php

namespace App\Http\Requests\Department;

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
            "*.code" => ["unique:departments,code", "distinct"],
            "*.business_unit" => [
                "required",
                "exists:business_units,name,deleted_at,NULL",
            ],
        ];
    }

    public function attributes()
    {
        return [
            "*.code" => "code",
            "*.business_unit_id" => "business unit",
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
