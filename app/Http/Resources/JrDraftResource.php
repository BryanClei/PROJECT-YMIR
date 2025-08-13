<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JrDraftResource extends JsonResource
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
            "jr_draft_id" => $this->jr_draft_id,
            "jo_description" => $this->jo_description,
            "helpdesk_id" => $this->helpdesk_id,
            "date_needed" => $this->date_needed,

            "user" => [
                "user_id" => $this->users->id,
                "prefix_id" => $this->users->prefix_id,
                "id_number" => $this->users->id_number,
                "first_name" => $this->users->first_name,
                "middle_name" => $this->users->middle_name,
                "last_name" => $this->users->last_name,
            ],
            "one_charging_id" => $this->one_charging_id,
            "one_charging_sync_id" => $this->one_charging_sync_id,
            "one_charging_code" => $this->one_charging_code,
            "one_charging_name" => $this->one_charging_name,
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
            "supplier_id" => $this->supplier_id,
            "supplier_name" => $this->supplier_name,
            "total_price" => $this->total_price,
            "assets" => $this->assets,
            "module_name" => $this->module_name,
            "status" => $this->status,
            "description" => $this->description,
            "reason" => $this->reason,
            "created_at" => $this->created_at,
            "helpdesk_id" => $this->helpdesk_id,
            "rush" => $this->rush,
            "outside_labor" => $this->outside_labor,
            "cap_ex" => $this->cap_ex,
            "direct_po" => $this->direct_po,
            "ship_to_id" => $this->ship_to_id,
            "ship_to_name" => $this->ship_to_name,
            "for_po_only" => $this->for_po_only,
            "for_po_only_id" => $this->for_po_only_id,
            "order_jo_transaction_id" => $this->id,
            "order" => JrItemDraftResource::collection($this->order),
        ];
    }
}
