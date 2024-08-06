<?php

namespace App\Http\Requests\PurchaseRequest;

use Illuminate\Foundation\Http\FormRequest;

class UploadRequest extends FormRequest
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
            "files" => "nullable|array|min:1",
            "files.*" => [
                "nullable",
                "max:25000",
                function ($attribute, $value, $fail) {
                    $extension = strtolower(
                        $value->getClientOriginalExtension()
                    );
                    if (
                        in_array($extension, [
                            "mp4",
                            "avi",
                            "mov",
                            "wmv",
                            "mkv",
                            "flv",
                            "mpeg",
                            "mpg",
                            "webm",
                            "3gp",
                            "m4v",
                            "ogv",
                            "ts",
                            "vob",
                        ])
                    ) {
                        $fail(
                            "The $attribute contains a video format. Which is not permitted"
                        );
                    }
                },
            ],
        ];
    }
}
