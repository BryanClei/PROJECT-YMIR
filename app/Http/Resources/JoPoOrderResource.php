<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JoPoOrderResource extends JsonResource
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
            "jo_po_id" => $this->jo_po_id,
            "description" => $this->description,
            "uom" => $this->uom_id,
            "quantity" => $this->quantity,
            "quantity_serve" => $this->quantity_serve,
            "unit_price" => $this->unit_price,
            "total_price" => $this->total_price,
            "remarks" => $this->remarks,
            "attachment" => empty($this->attachment) ? null : $this->attachment,
            "asset" => [
                "asset" => $this->asset,
                "asset_code" => $this->asset_code,
            ],
            "helpdesk_id" => $this->helpdesk_id,
        ];
    }
}
