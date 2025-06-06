<?php

namespace App\Http\Requests\DepartmentUnit;

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
                $this->route()->units_department
                    ? "unique:department_units,code," .
                        $this->route()->units_department .
                        ",id,department_id," .
                        $this->input("department_id")
                    : "unique:department_units,code,NULL,id,department_id," .
                        $this->input("department_id"),
            ],
            "department_id" => [
                "required",
                "exists:departments,id,deleted_at,NULL",
            ],
        ];
    }
}
