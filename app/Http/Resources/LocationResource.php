<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
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
            "sub_units" => $this->sub_units->map(function ($item) {
                return [
                    "id" => $item->id,
                    "name" => $item->name,
                    "code" => $item->code,
                    "updated_at" => $this->updated_at,
                    "deleted_at" => $this->deleted_at,
                ];
            }),
        ];
    }
}
