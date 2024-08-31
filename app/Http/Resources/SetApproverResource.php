<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SetApproverResource extends JsonResource
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
            "po_settings_id" => $this->po_settings_id,
            "approver_id" => $this->approver_id,
            "approver_name" => $this->approver_name,
            "price_range" => $this->price_range,
            "layer" => $this->layer,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
