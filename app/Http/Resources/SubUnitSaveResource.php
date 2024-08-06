<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubUnitSaveResource extends JsonResource
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

            "department units" => [
                "id" => $this->department_unit->id,
                "name" => $this->department_unit->name,
                "code" => $this->department_unit->code,
                "updated_at" => $this->department_unit->updated_at,
                "deleted_at" => $this->department_unit->deleted_at,
            ],
        ];
    }
}
