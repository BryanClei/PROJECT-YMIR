<?php

namespace App\Http\Resources;

use App\Models\AllowablePercentage;
use Illuminate\Http\Resources\Json\JsonResource;

class PoItemResource extends JsonResource
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
            "pr_id" => $this->pr_id,
            "po_id" => $this->po_id,
            // "reference_no" => $this->reference_no,
            "pr_item_id" => $this->pr_item_id,
            "item" => [
                "id" => $this->item_id,
                "name" => $this->item_name,
                "code" => $this->item_code,
                "allowable" => $this->items->allowable ?? null,
                "allowable_percentage" => new AllowablePercentageResource(
                    AllowablePercentage::first()
                ),
                "items" => $this->items,
            ],
            "uom" => $this->uom,
            "price" => $this->price,
            "quantity" => $this->quantity,
            "quantity_serve" => $this->quantity_serve,
            "total_price" => $this->total_price,
            "supplier_id" => [
                "id" => $this->supplier->id ?? null,
                "name" => $this->supplier->name ?? null,
                "code" => $this->supplier->code ?? null,
            ],
            "attachment" => $this->pr_item->attachment ?? null,
            "canvassing" => $this->attachment
                ? json_decode($this->attachment, true)
                : null,
            "buyer_id" => $this->buyer_id,
            "buyer_name" => $this->buyer_name,
            "remarks" => $this->remarks,
            "updated_at" => $this->updated_at,
            "warehouse_id" => $this->warehouse ?? null,
            "category_id" => $this->category ?? null,
            "rr_order" => $this->rr_orders ?? null,
        ];
    }
}
