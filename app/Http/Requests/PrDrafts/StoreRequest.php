<?php

namespace App\Http\Requests\PrDrafts;

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
            "pr_draft_id" => [
                $this->route()->pr_draft
                    ? "unique:pr_drafts,pr_draft_id," . $this->route()->pr_draft
                    : "unique:pr_drafts,pr_draft_id",
            ],
            "one_charging_sync_id" => [
                "exists:one_charging,id,deleted_at,NULL",
            ],
            "company_id" => "exists:companies,id,deleted_at,NULL",
            "business_unit_id" => "exists:business_units,id,deleted_at,NULL",
            "department_id" => "exists:departments,id,deleted_at,NULL",
            "department_unit_id" =>
                "exists:department_units,id,deleted_at,NULL",
            "sub_unit_id" => "exists:sub_units,id,deleted_at,NULL",
            "location_id" => "exists:locations,id,deleted_at,NULL",
            "order" => "required|array|min:1",
            // "order.*.quantity" => "required|integer|min:1",
            // "order.*.uom_id" => "required|exists:uoms,id",
            // "order.*.category_id" => "required|exists:categories,id",
            // "order.*.warehouse_id" => "required|exists:warehouses,id",
        ];
    }
}
