<?php

namespace App\Http\Resources;

use App\Http\Resources\DepartmentUnitResource;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
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
            "name" => $this->name,
            "code" => $this->code,
            "updated_at" => $this->updated_at,
            "deleted_at" => $this->deleted_at,
            "department_unit" => [
                "id" => $this->department_units->id,
                "name" => $this->department_units->name,
                "code" => $this->department_units->code,
                "updated_at" => $this->department_units->updated_at,
                "deleted_at" => $this->department_units->deleted_at,
            ],
        ];
    }
}
