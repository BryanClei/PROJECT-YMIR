<?php

namespace App\Http\Requests\User;

use Illuminate\Validation\Rule;
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
            "prefix_id" => ["required", "string"],

            "id_number" => [
                "required",
                Rule::unique("users", "id_number")->ignore(
                    $this->route("user")
                ),
                "numeric",
            ],

            "first_name" => ["required"],

            "middle_name" => ["string"],

            "last_name" => "required",

            "suffix" => ["string", "nullable"],

            "position_name" => ["required", "string", "max:255"],

            "company_id" => [
                "required",
                "exists:companies,id,deleted_at,NULL",
                "numeric",
            ],

            "business_unit_id" => [
                "required",
                "exists:business_units,id,deleted_at,NULL",
                "numeric",
            ],

            "department_id" => [
                "required",
                "exists:departments,id,deleted_at,NULL",
                "numeric",
            ],
            "department_unit_id" => [
                "required",
                "exists:department_units,id,deleted_at,NULL",
                "numeric",
            ],

            "sub_unit_id" => [
                "required",
                "exists:sub_units,id,deleted_at,NULL",
                "numeric",
            ],

            "location_id" => [
                "required",
                "exists:locations,id,deleted_at,NULL",
                "numeric",
            ],

            "warehouse_id" => [
                "required",
                "exists:warehouses,id,deleted_at,NULL",
                "numeric",
            ],
            "username" => [
                "required",
                Rule::unique("users", "username")->ignore($this->route("user")),
            ],

            "role_id" => [
                "required",
                "exists:roles,id,deleted_at,NULL",
                "numeric",
            ],
        ];
    }
}
