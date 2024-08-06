<?php

namespace App\Http\Requests\Item;

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
                $this->route()->item
                    ? "unique:items,code," . $this->route()->item
                    : "unique:items,code",
                "string",
            ],
            "name" => ["required", "string"],
            "uom_id" => ["required", "exists:uoms,id,deleted_at,NULL"],
            "category_id" => [
                "required",
                "exists:categories,id,deleted_at,NULL",
            ],
            "type" => "required"
        ];
    }
}
