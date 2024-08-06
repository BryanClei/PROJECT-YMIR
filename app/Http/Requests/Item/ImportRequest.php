<?php

namespace App\Http\Requests\Item;

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
            "*.code" => ["unique:items,code", "distinct"],
            "*.uom_id" => ["exists:uoms,name,deleted_at,NULL"],
            "*.category_id" => ["exists:categories,name,deleted_at,NULL"],
        ];
    }

    public function attributes()
    {
        return [
            "*.code" => "code",
            "*.uom_id" => "uom",
            "*.category_id" => "category",
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
