<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BusinessUnitSaveResource extends JsonResource
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
            "company" => [
                "id" => $this->company->id,
                "name" => $this->company->name,
                "code" => $this->company->code,
                "updated_at" => $this->company->updated_at,
                "deleted_at" => $this->company->deleted_at,
            ],
        ];
    }
}
