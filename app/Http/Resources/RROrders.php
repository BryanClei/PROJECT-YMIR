<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RROrders extends JsonResource
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
            "item_id" => $this->item_id,
            "item_code" => $this->item_code,
            "item_name" => $this->item_name,
            "quantity_receive" => $this->quantity_receive,
            "remaining" => $this->remaining,
        ];
    }
}
