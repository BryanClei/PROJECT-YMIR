<?php

namespace App\Http\Requests\NoRequisitionJR;

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
            "jo_number" => [
                $this->route()->job_order_transaction
                    ? "unique:jo_transactions,jo_number," .
                        $this->route()->job_order_transaction
                    : "unique:jo_transactions,jo_number",
            ],
            "company_id" => "exists:companies,id,deleted_at,NULL",
            "business_unit_id" => "exists:business_units,id,deleted_at,NULL",
            "department_id" => "exists:departments,id,deleted_at,NULL",
            "department_unit_id" =>
                "exists:department_units,id,deleted_at,NULL",
            "sub_unit_id" => "exists:sub_units,id,deleted_at,NULL",
            "location_id" => "exists:locations,id,deleted_at,NULL",
            "order" => "required|array|min:1",
            "order.*.description" => "required|string",
            "order.*.uom_id" => "required|integer|exists:uoms,id",
            "order.*.quantity" => "required|numeric|min:1",
            "order.*.remarks" => "nullable|string",
            "order.*.attachment" => "required",
        ];
    }
}
