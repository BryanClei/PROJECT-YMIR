<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JrItemDraftResource extends JsonResource
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
            "jr_draft_id" => $this->jr_draft_id,
            "description" => $this->description,
            "uom" => $this->uom,
            "quantity" => $this->quantity,
            "unit_price" => $this->unit_price,
            "total_price" => $this->total_price,
            "remarks" => $this->remarks,
            "asset" => [
                "asset" => $this->asset,
                "asset_code" => $this->asset_code,
            ],
            "helpdesk_id" => $this->helpdesk_id,
        ];
    }
}
