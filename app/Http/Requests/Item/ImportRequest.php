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
            // Validate each item in the root array
            "*" => "array",
            "*.code" => ["required", "unique:items,code", "distinct"],
            "*.type" => ["required", "exists:types,name,deleted_at,NULL"],
            "*.uom" => ["required", "exists:uoms,name,deleted_at,NULL"],
            "*.category" => [
                "required",
                "exists:categories,name,deleted_at,NULL",
            ],

            // Validate warehouse array
            "*.warehouse" => "array",
            "*.warehouse.*.warehouse" => [
                "exists:warehouses,name,deleted_at,NULL",
            ],

            // Validate small tools array
            "*.small_tools" => "nullable|array",
            "*.small_tools.*.name" => [
                "exists:small_tools,name,deleted_at,NULL",
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            "*.code" => "code",
            "*.type" => "type",
            "*.uom" => "uom",
            "*.category" => "category",
            "*.warehouse.*.warehouse" => "warehouse",
            "*.small_tools.*.name" => "small tool name",
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            "unique" => "This :input is already taken.",
            "distinct" => "This :input has a duplicate value.",
            "exists" => "This :attribute does not exist.",
            "required" => "The :attribute field is required.",
        ];
    }
}
