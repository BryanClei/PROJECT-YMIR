<?php

namespace App\Http\Requests\Department;

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
                $this->route()->department
                    ? "unique:departments,code," . $this->route()->department
                    : "unique:departments,code",
            ],
            "business_unit_id" => [
                "required",
                "exists:business_units,id,deleted_at,NULL",
            ],
        ];
    }
}
