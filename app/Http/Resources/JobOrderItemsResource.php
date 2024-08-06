<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JobOrderItemsResource extends JsonResource
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
            "jo_transaction_id" => $this->jo_transaction_id,
            "description" => $this->description,
            "uom" => $this->uom_id,
            "po_at" => $this->po_at,
            "purchase_order_id" => $this->purchase_order_id,
            "quantity" => $this->quantity,
            "unit_price" => $this->unit_price,
            "total_price" => $this->total_price,
            "remarks" => $this->remarks,
            "attachment" => $this->attachment,
            "asset" => $this->asset,
        ];
    }
}
