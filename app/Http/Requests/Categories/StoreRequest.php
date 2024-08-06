<?php

namespace App\Http\Requests\Categories;

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
                $this->route()->category
                    ? "unique:categories,name," . $this->route()->category
                    : "unique:categories,name",
                "string",
            ],
            "code" => [
                "required",
                "string",
                $this->route()->category
                    ? "unique:categories,code," . $this->route()->category
                    : "unique:categories,code",
            ],
        ];
    }
}
