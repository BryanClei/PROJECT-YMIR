<?php

namespace App\Http\Resources\ItemWarehouseResource;

use Illuminate\Http\Resources\Json\JsonResource;

class ItemWarehouseResource extends JsonResource
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
            // "id" => $this->id,
            "id" => $this->warehouse_id,
            "item_id" => $this->item_id,
            "warehouse_name" => $this->warehouse->name,
            "warehouse_code" => $this->warehouse->code,
            "url" => $this->warehouse->url,
            "token" => $this->warehouse->token,
        ];
    }
}
