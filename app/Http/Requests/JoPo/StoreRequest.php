<?php

namespace App\Http\Requests\JoPo;

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
            "jo_number" => [
                "required",
                "exists:jo_transactions,jo_number,deleted_at,NULL",
            ],
        ];
    }
}
