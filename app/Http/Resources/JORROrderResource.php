<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JORROrderResource extends JsonResource
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
            "jo_rr_year_number_id" =>
                $this->jo_rr_transaction->jo_rr_year_number_id,
            "jr_year_number_id" => $this->jo_transaction->jo_year_number_id,
            "jo_year_number_id" => $this->jo_po_transaction->po_year_number_id,
            "rr_number" => $this->jo_rr_transaction->id,
            "jo_number" => $this->jo_rr_transaction->jo_id,
            "po_number" => $this->jo_rr_transaction->jo_po_id,
            "tagging_id" => $this->jo_rr_transaction->tagging_id,
            "uom_id" => $this->order->uom,
            "requested_by" => $this->jo_transaction->users,
            "description" => $this->order->description,
            "price" => $this->order->unit_price,
            "total_price" => $this->order->total_price,
            "quantity_receive" => $this->quantity_receive,
            "remaining" => $this->remaining,
            "shipment_no" => $this->shipment_no,
            "delivery_date" => $this->delivery_date,
            "rr_date" => $this->rr_date,
            "jo_po_orders" => $this->order,
            "jr_transaction" => $this->jo_transaction,
            "jo_transaction" => $this->jo_po_transaction,
            "rr_transaction" => $this->jo_rr_transaction,
        ];
    }
}
