<?php

namespace App\Http\Resources;

use App\Http\Resources\PrItemDraftResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PrDraftResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "pr_draft_id" => $this->pr_draft_id,
            "pr_description" => $this->pr_description,
            "helpdesk_id" => $this->helpdesk_id,
            "date_needed" => $this->date_needed,
            "user" => $this->users
                ? [
                    "prefix_id" => $this->users->prefix_id,
                    "id_number" => $this->users->id_number,
                    "first_name" => $this->users->first_name,
                    "middle_name" => $this->users->middle_name,
                    "last_name" => $this->users->last_name,
                    "mobile_no" => $this->users->mobile_no,
                    "warehouse" => $this->users->warehouse
                        ? [
                            "warehouse_id" => $this->users->warehouse->id,
                            "warehouse_code" => $this->users->warehouse->code,
                            "warehouse_name" => $this->users->warehouse->name,
                        ]
                        : null,
                ]
                : null,

            "type" => [
                "id" => $this->type_id,
                "name" => $this->type_name,
            ],
            "businessUnit" => [
                "id" => $this->business_unit_id,
                "name" => $this->business_unit_name,
            ],
            "company" => [
                "id" => $this->company_id,
                "name" => $this->company_name,
            ],
            "department" => [
                "id" => $this->department_id,
                "name" => $this->department_name,
            ],
            "departmentUnit" => [
                "id" => $this->department_unit_id,
                "name" => $this->department_unit_name,
            ],
            "location" => [
                "id" => $this->location_id,
                "name" => $this->location_name,
            ],
            "subUnit" => [
                "id" => $this->sub_unit_id,
                "name" => $this->sub_unit_name,
            ],
            "accountTitle" => [
                "id" => $this->account_title_id,
                "name" => $this->account_title_name,
            ],
            "asset" => [
                "asset" => $this->asset,
                "asset_code" => $this->asset_code ?? null,
            ],
            "cap_ex" => $this->cap_ex,
            "sgp" => $this->sgp,
            "f1" => $this->f1,
            "f2" => $this->f2,
            "rush" => $this->rush,
            "for_po_only" => $this->for_po_only,
            "for_po_only_id" => $this->for_po_only_id,
            "supplier_name" => $this->supplier_name,
            "supplier_id" => $this->supplier_id,
            "for_marketing" => $this->for_marketing,
            "module_name" => $this->module_name,
            "status" => $this->status,
            "description" => $this->description,
            "created_at" => $this->created_at,
            "order" => PrItemDraftResource::collection($this->order),
        ];
    }
}
