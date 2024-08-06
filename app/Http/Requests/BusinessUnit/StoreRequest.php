<?php

namespace App\Http\Requests\BusinessUnit;

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
                $this->route()->business_unit
                    ? "unique:business_units,code," .
                        $this->route()->business_unit
                    : "unique:business_units,code",
            ],
            "company_id" => ["required", "exists:companies,id,deleted_at,NULL"],
        ];
    }
}
