<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
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
            "old_password" => "required|current_password|different:field",
            "password" => "required|different:old_password",
            "confirm_password" => "required|same:password",
        ];
    }
    public function attributes()
    {
        return [
            "old_password" => "Old password",
            "password" => "New password",
            "confirm_password" => "Confirm password",
        ];
    }

    public function messages()
    {
        return [
            "required" => "The :attribute is required.",
            "current_password" => "The :attribute is incorrect.",
            "different" =>
                ":attribute must be different from the old password.",
            "same" => "The :attribute does not match with new password.",
        ];
    }
}
