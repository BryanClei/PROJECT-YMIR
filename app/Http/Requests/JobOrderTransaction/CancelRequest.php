<?php

namespace App\Http\Requests\JobOrderTransaction;

use Illuminate\Foundation\Http\FormRequest;

class CancelRequest extends FormRequest
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
            // Ensure 'reason' is provided and is a string with a max length
            "reason" => "required|string|max:255",
        ];
    }
}
