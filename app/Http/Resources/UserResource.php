<?php

namespace App\Http\Resources;

use App\Http\Resources\RoleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            "prefix_id" => $this->prefix_id,
            "id_number" => $this->id_number,
            "position" => $this->position_name,
            "mobile_no" => $this->mobile_no,
            "name" => [
                "first_name" => $this->first_name,
                "middle_name" => $this->middle_name,
                "last_name" => $this->last_name,
                "suffix" => $this->suffix,
            ],
            "one_charging_id" => $this->one_charging_id,
            "one_charging_sync_id" => $this->one_charging_sync_id,
            "one_charging_code" => $this->one_charging_code,
            "one_charging_name" => $this->one_charging_name,
            "company" =>
                [
                    "id" => $this->company->id,
                    "name" => $this->company->name,
                    "code" => $this->company->code,
                    "updated_at" => $this->company->updated_at,
                    "deleted_at" => $this->company->deleted_at,
                ] ?? null,
            "business_unit" =>
                [
                    "id" => $this->business_unit->id,
                    "name" => $this->business_unit->name,
                    "code" => $this->business_unit->code,
                    "updated_at" => $this->business_unit->updated_at,
                    "deleted_at" => $this->business_unit->deleted_at,
                ] ?? null,
            "department" => [
                "id" => $this->department->id,
                "name" => $this->department->name,
                "code" => $this->department->code,
                "updated_at" => $this->department->updated_at,
                "deleted_at" => $this->department->deleted_at,
            ],
            "department_units" => [
                "id" => $this->department_unit->id,
                "name" => $this->department_unit->name,
                "code" => $this->department_unit->code,
                "updated_at" => $this->department_unit->updated_at,
                "deleted_at" => $this->department_unit->deleted_at,
            ],
            "sub_unit" => [
                "id" => $this->sub_unit->id,
                "name" => $this->sub_unit->name,
                "code" => $this->sub_unit->code,
                "updated_at" => $this->sub_unit->updated_at,
                "deleted_at" => $this->sub_unit->deleted_at,
            ],
            "location" => [
                "id" => $this->location->id ?? null,
                "name" => $this->location->name ?? null,
                "code" => $this->location->code ?? null,
                "updated_at" => $this->location->updated_at ?? null,
                "deleted_at" => $this->location->deleted_at ?? null,
            ],
            "warehouse" => [
                "id" => $this->warehouse->id,
                "name" => $this->warehouse->name,
                "code" => $this->warehouse->code,
                "updated_at" => $this->warehouse->updated_at,
                "deleted_at" => $this->warehouse->deleted_at,
            ],
            "username" => $this->username,
            "updated_at" => $this->updated_at,
            "deleted_at" => $this->deleted_at,
            "role" => new RoleResource($this->role),
        ];
    }
}
