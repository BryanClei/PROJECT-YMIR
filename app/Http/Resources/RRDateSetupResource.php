<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RRDateSetupResource extends JsonResource
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
            "received_receipt_date" => $this->rr_date,
            "delivery_date" => $this->delivery_date,
            "created_date" => $this->created_date,
            "update_at" => $this->updated_at,
            "deleted_at" => $this->deleted_at,
        ];
    }
}
