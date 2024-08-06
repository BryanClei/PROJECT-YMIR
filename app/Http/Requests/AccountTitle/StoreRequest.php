<?php

namespace App\Http\Requests\AccountTitle;

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
                $this->route()->account_title
                    ? "unique:account_titles,code," .
                        $this->route()->account_title
                    : "unique:account_titles,code",
            ],
            "account_type_id" => [
                "required",
                "exists:account_types,id,deleted_at,NULL",
            ],
            "account_group_id" => [
                "required",
                "exists:account_groups,id,deleted_at,NULL",
            ],
            "account_sub_group_id" => [
                "required",
                "exists:account_sub_groups,id,deleted_at,NULL",
            ],
            "financial_statement_id" => [
                "required",
                "exists:account_financial_statement,id,deleted_at,NULL",
            ],
            "normal_balance_id" => [
                "required",
                "exists:account_normal_balance,id,deleted_at,NULL",
            ],
            "account_title_unit_id" => [
                "required",
                "exists:account_title_units,id,deleted_at,NULL",
            ],
        ];
    }
}
