<?php

namespace App\Http\Requests\Buyer;

use App\Models\PRItems;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class ReturnRequest extends FormRequest
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
        $user_id = Auth()->user()->id;

        return [
            "id" => "required|array",
            "id.*" => [
                "exists:pr_items,id,deleted_at,NULL,buyer_id," . $user_id,
            ],
        ];
    }
}
