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
            "*.type" => ["exists:types,name,deleted_at,NULL"],
            "*.uom" => ["exists:uoms,name,deleted_at,NULL"],
            "*.category" => ["exists:categories,name,deleted_at,NULL"],
            "warehouse.*.warehouse" => [
                "exists:warehouse,name,deleted_at,NULL",
            ],
            "small_tools" => "nullable|array",
            "small_tools.*.small_tools_id" =>
                "exists:small_tools,id,deleted_at,NULL",
        ];
    }

    public function attributes()
    {
        return [
            "*.code" => "code",
            "*.type" => "type",
            "*.uom" => "uom",
            "*.category" => "category",
            "warehouse.*.warehouse" => "warehouse",
        ];
    }

    public function message()
    {
        return [
            "unique" => "This :Attribute is already been taken.",
            "distinct" => "This :Attribute has duplicate value.",
            "exists" => "This :Attribute is not exists.",
        ];
    }
}
