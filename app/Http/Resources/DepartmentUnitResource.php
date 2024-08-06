<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentUnitResource extends JsonResource
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
            "sub_unit" => [
                "id" => $this->sub_unit->id,
                "name" => $this->sub_unit->name,
                "code" => $this->sub_unit->code,
                "updated_at" => $this->sub_unit->updated_at,
                "deleted_at" => $this->sub_unit->deleted_at,
            ],
        ];
    }
}
