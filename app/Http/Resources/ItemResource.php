<?php

namespace App\Http\Resources;

use App\Http\Resources\UomResource;
use App\Models\AllowablePercentage;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ItemWarehouseResource\ItemWarehouseResource;

class ItemResource extends JsonResource
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
            "code" => $this->code,
            "name" => $this->name,
            "uom" => [
                "id" => $this->uom->id,
                "name" => $this->uom->name,
                "code" => $this->uom->code,
                "is_integer" => $this->uom->is_integer,
            ],
            "category" => [
                "id" => $this->category->id,
                "name" => $this->category->name,
                "code" => $this->category->code,
            ],
            "type" => $this->types
                ? [
                    "id" => $this->types->id,
                    "name" => $this->types->name,
                ]
                : null,
            "warehouses" => ItemWarehouseResource::collection(
                $this->warehouse
            ),
            "allowable_percentage" => AllowablePercentage::get(),
            "allowable" => $this->allowable,
            "updated_at" => $this->updated_at,
            "deleted_at" => $this->deleted_at,
        ];
    }
}
