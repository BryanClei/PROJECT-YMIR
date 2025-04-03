<?php

namespace App\Http\Requests\Etd;

use Illuminate\Foundation\Http\FormRequest;

class SyncRequest extends FormRequest
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
            "*.item_id.*.id" => [
                "required",
                "exists:rr_orders,id,deleted_at,NULL",
            ],
        ];
    }

    public function attributes()
    {
        return [
            "*.item_id.*.id" => "ID",
        ];
    }

    public function messages()
    {
        return [
            "exists" => ":Attribute :input is not exists.",
        ];
    }
}
