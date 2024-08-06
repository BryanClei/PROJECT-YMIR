<?php

namespace App\Http\Resources;

use App\Http\Resources\JoPoResource;
use App\Http\Resources\JORROrderResource;
use Illuminate\Http\Resources\Json\JsonResource;

class JORRResource extends JsonResource
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
            "jo_po_id" => $this->jo_po_id,
            "jo_id" => $this->jo_id,
            "received_by" => $this->received_by,
            "tagging_id" => $this->tagging_id,
            "rr_orders" => JORROrderResource::collection($this->rr_orders),
            "jo_po_transaction" => new JoPoResource($this->jo_po_transactions),
        ];
    }
}
