<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PrItemDraftResource extends JsonResource
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
            "pr_draft_id" => $this->transaction_id,
            "items" => new ItemResource($this->item),
            "item" => [
                "id" => $this->id,
                "name" => $this->item_name,
                "code" => $this->item_code,
            ],
            "uom" => $this->uom_id,
            "quantity" => $this->quantity,
            "unit_price" => $this->unit_price,
            "total_price" => $this->total_price,
            "remarks" => $this->remarks,
            "assets" => $this->asset,
            "warehouse_id" => $this->warehouse_id,
            "category_id" => $this->category,
        ];
    }
}
