<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PRItemsResource extends JsonResource
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
            // "transaction_id" => $this->transaction_id,
            // "item_code" => $this->item_code,
            "id" => $this->id,
            "transaction_no" => $this->transaction_id ?? null,
            "reference_no" => $this->reference_no ?? null,
            "items" => new ItemResource($this->item),
            "item" => [
                "id" => $this->item_id,
                "name" => $this->item_name,
                "code" => $this->item_code,
            ],
            "uom" => $this->uom_id,
            "po_at" => $this->po_at,
            "purchase_order_id" => $this->purchase_order_id,
            "buyer_id" => $this->buyer_id,
            "buyer_name" => $this->buyer_name,
            "supplier_id" => $this->supplier_id,
            "quantity" => $this->quantity,
            "remarks" => $this->remarks,
            "attachment" => is_array($this->attachment)
                ? $this->attachment
                : json_decode($this->attachment ?? "{}", true),
            "assets" => $this->asset,
            "warehouse_id" => $this->warehouse_id,
            "category_id" => $this->category,
        ];
    }
}
