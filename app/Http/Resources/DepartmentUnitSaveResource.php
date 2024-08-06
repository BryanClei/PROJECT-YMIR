<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentUnitSaveResource extends JsonResource
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
            "department" => [
                "id" => $this->department->id,
                "name" => $this->department->name,
                "code" => $this->department->code,
                "updated_at" => $this->department->updated_at,
                "deleted_at" => $this->department->deleted_at,
            ],
        ];
    }
}
