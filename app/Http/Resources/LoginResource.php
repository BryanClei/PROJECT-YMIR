<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
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
            "name" => [
                "first_name" => $this->first_name,
                "last_name" => $this->last_name,
                "middle_name" => $this->middle_name,
                "suffix" => $this->suffix,
            ],
            "position" => $this->position_name,
            "company" => [
                "id" => $this->company->id,
                "name" => $this->company->name,
                "code" => $this->company->code,
            ],
            "business_unit" => [
                "id" => $this->business_unit->id,
                "name" => $this->business_unit->name,
                "code" => $this->business_unit->code,
            ],
            "department" => [
                "id" => $this->department->id,
                "name" => $this->department->name,
                "code" => $this->department->code,
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
            ],
            "location" => [
                "id" => $this->location->id,
                "name" => $this->location->name,
                "code" => $this->location->code,
            ],
            "warehouse" => [
                "id" => $this->warehouse->id,
                "name" => $this->warehouse->name,
                "code" => $this->warehouse->code,
            ],
            "username" => $this->username,
            "updated_at" => $this->updated_at,
            "token" => $this->token,
            "role" => new RoleResource($this->role),
        ];
    }
}
