<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ETDApiResource extends JsonResource
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
            "pr_number" => $this->po_transaction->pr_transaction->pr_number,
            "pr_year_number_id" => $this->pr_transaction->pr_year_number_id,
            "pr_date" => $this->pr_transaction->approved_at,
            "po_number" => $this->po_transaction->po_number,
            "po_date" => $this->po_transaction->approved_at,
            "item_code" => $this->item_code,
            "item_name" => $this->item_name,
            "ordered" => $this->pr_transaction->pr_items->quantity,
            "delivered" => $this->quantity_serve,
            "uom" => $this->uom->name,
            "unit_price" => $this->price,
            "supplier_name" => $this->supplier->name,
        ];
    }
}
