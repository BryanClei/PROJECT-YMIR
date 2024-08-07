<?php

namespace App\Http\Requests\AccountTitle;

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
            "*.name" => ["required", "unique:account_titles,name", "distinct"],
            "*.code" => [
                "required",
                "string",
                $this->route()->account_title
                    ? "unique:account_titles,code," .
                        $this->route()->account_title
                    : "unique:account_titles,code",
            ],
            "*.account_type" => [
                "required",
                "exists:account_types,name,deleted_at,NULL",
            ],
            "*.account_group" => [
                "required",
                "exists:account_groups,name,deleted_at,NULL",
            ],
            "*.account_sub_group" => [
                "required",
                "exists:account_sub_groups,name,deleted_at,NULL",
            ],
            "*.financial_statement" => [
                "required",
                "exists:account_financial_statement,name,deleted_at,NULL",
            ],
            "*.normal_balance" => [
                "required",
                "exists:account_normal_balance,name,deleted_at,NULL",
            ],
            "*.account_title_unit" => [
                "required",
                "exists:account_title_units,name,deleted_at,NULL",
            ],
        ];
    }

    public function attributes()
    {
        return [
            "*.name" => "name",
            "*.code" => "code",
        ];
    }

    public function messages()
    {
        return [
            "*.code.unique" => "This :Attribute is already been taken.",
            "distinct" => "This :Attribute has duplicate value.",
            "exists" => "This :Attribute is not exist.",
        ];
    }
}
