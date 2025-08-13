<?php

namespace App\Http\Resources;

use Carbon\Carbon;
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
        $tagAging = null;
        if ($this->tagged_buyer) {
            $currentTime = Carbon::now()->timezone("Asia/Manila");
            $taggedTime = Carbon::parse($this->tagged_buyer)->timezone(
                "Asia/Manila"
            );
            $tagAging = $currentTime->diffInDays($taggedTime);
        }

        return [
            // "transaction_id" => $this->transaction_id,
            // "item_code" => $this->item_code,
            "id" => $this->id,
            "transaction_no" => $this->transaction_id ?? null,
            "reference_no" => $this->reference_no ?? null,
            "items" => new ItemResource($this->item),
            "item" => [
                "id" => $this->id,
                "name" => $this->item_name,
                "code" => $this->item_code,
            ],
            "uom" => $this->uom,
            "po_at" => $this->po_at,
            "purchase_order_id" => $this->purchase_order_id,
            "buyer_id" => $this->buyer_id,
            "buyer_name" => $this->buyer_name,
            "tagged_buyer" => $this->tagged_buyer,
            "tag_aging" => $tagAging,
            "supplier_id" => $this->supplier_id,
            "original_quantity" => $this->quantity,
            "quantity" => $this->quantity - $this->partial_received,
            "partial_received" => $this->partial_received,
            "remaining_qty" => $this->remaining_qty,
            "unit_price" => $this->unit_price,
            "total_price" => $this->total_price,
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
