<?php

namespace App\Http\Resources\OneCharging;

use Illuminate\Http\Resources\Json\JsonResource;

class OneChargingResource extends JsonResource
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
            "sync_id" => $this->sync_id,
            "code" => $this->code,
            "name" => $this->name,
            "businessUnit" => [
                "id" => $this->business_unit_id,
                "name" => $this->business_unit_name,
                "code" => $this->business_unit_code,
            ],
            "company" => [
                "id" => $this->company_id,
                "name" => $this->company_name,
                "code" => $this->company_code,
            ],
            "department" => [
                "id" => $this->department_id,
                "name" => $this->department_name,
                "code" => $this->department_code,
            ],
            "departmentUnit" => [
                "id" => $this->department_unit_id,
                "name" => $this->department_unit_name,
                "code" => $this->department_unit_code,
            ],
            "location" => [
                "id" => $this->location_id,
                "name" => $this->location_name,
                "code" => $this->location_code,
            ],
            "subUnit" => [
                "id" => $this->sub_unit_id,
                "name" => $this->sub_unit_name,
                "code" => $this->sub_unit_code,
            ],
            "created_at" => $this->created_at,
            "deleted_at" => $this->deleted_at,
        ];
    }
}
