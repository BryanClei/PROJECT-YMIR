<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PoSettingsResource extends JsonResource
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
            "module" => $this->module,
            "company" => [
                "company_id" => $this->company_id,
                "company_name" => $this->company_name,
            ],
            "business" => [
                "buisness_unit_id" => $this->business_unit_id,
                "buisness_unit_name" => $this->business_unit_name,
            ],
            "department" => [
                "department_id" => $this->department_id,
                "department_name" => $this->department_name,
            ],
            "one_charging_id" => $this->one_charging_id,
            "one_charging_sync_id" => $this->one_charging_sync_id,
            "one_charging_code" => $this->one_charging_code,
            "one_charging_name" => $this->one_charging_name,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "deleted_at" => $this->deleted_at,
            "set_approver" => SetApproverResource::collection(
                $this->set_approver
            ),
        ];
    }
}
