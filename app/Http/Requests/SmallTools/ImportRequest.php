<?php

namespace App\Http\Requests\SmallTools;

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
     */ public function rules()
    {
        return [
            "*.code" => ["required", "unique:small_tools,code", "distinct"],
            "*.name" => ["required"],
        ];
    }

    public function attributes()
    {
        return [
            "*.code" => "code",
            "*.name" => "name",
        ];
    }

    public function message()
    {
        return [
            "unique" => ":Attribute is already been taken.",
            "distinct" => ":Attribute has duplicate value.",
            "exists" => ":Attribute is not exists.",
        ];
    }
}
