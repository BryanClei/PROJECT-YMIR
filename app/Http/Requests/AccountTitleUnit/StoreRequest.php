<?php

namespace App\Http\Requests\AccountTitleUnit;

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
            "name" => [
                "required",
                $this->route()->account_title_unit
                    ? "unique:account_title_units,name," .
                        $this->route()->account_title_unit
                    : "unique:account_title_units,name",
                "string",
            ],
        ];
    }
}
