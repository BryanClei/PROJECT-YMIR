<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentSaveResource extends JsonResource
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
            "business_unit" => [
                "id" => $this->business_unit->id,
                "name" => $this->business_unit->name,
                "code" => $this->business_unit->code,
                "updated_at" => $this->business_unit->updated_at,
                "deleted_at" => $this->business_unit->deleted_at,
            ],
        ];
    }
}
