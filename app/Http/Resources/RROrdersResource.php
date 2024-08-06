<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RROrdersResource extends JsonResource
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
            "rr_number" => $this->rr_transaction->id,
            "pr_number" => $this->rr_transaction->pr_id,
            "po_number" => $this->rr_transaction->po_id,
            "tagging_id" => $this->rr_transaction->tagging_id,
            "buyer_name" => $this->order->buyer_name ?? "",
            "uom_id" => $this->order->uom_id ?? "",
            "id" => $this->id,
            "item_id" => $this->item_id,
            "item_code" => $this->item_code,
            "item_name" => $this->item_name,
            "price" => $this->order->price ?? "",
            "total_price" => $this->order->total_price ?? "",
            "quantity_receive" => $this->quantity_receive,
            "remaining" => $this->remaining,
            "shipment_no" => $this->shipment_no,
            "delivery_date" => $this->delivery_date,
            "rr_date" => $this->rr_date,
        ];
    }
}
