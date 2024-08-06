<?php

namespace App\Http\Requests\Asset;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            "name" => "required",
            "tag_number" => [
                "required",
                Rule::unique("assets", "tag_number")->ignore($this->route('asset')),
                // Rule::exists("assets", "tag_number")->where(function($query) {
                //     $query->whereNull("deleted_at");
                // }),
            ]
        ];
    }
}
