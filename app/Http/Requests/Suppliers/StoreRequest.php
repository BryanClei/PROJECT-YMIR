<?php

namespace App\Http\Requests\Suppliers;

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
            "code" => [
                "required",
                $this->route()->supplier
                    ? "unique:suppliers,code," . $this->route()->supplier
                    : "unique:suppliers,code",
                "string",
            ],
            "name" => ["required", "string"],
            "term" => ["required", "numeric"],
        ];
    }
}
