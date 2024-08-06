<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PADisplay extends FormRequest
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
            "status" => [
                "required",
                "string",
                "in:to_po,rejected,approved,For approval,pending,for_po,tagged_buyer,return_po,cancelled",
            ],
        ];
    }
}
