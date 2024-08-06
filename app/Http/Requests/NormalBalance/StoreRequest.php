<?php

namespace App\Http\Requests\NormalBalance;

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
                $this->route()->normal_balance
                    ? "unique:account_normal_balance,name," .
                        $this->route()->normal_balance
                    : "unique:account_normal_balance,name",
                "string",
            ],
        ];
    }
}
