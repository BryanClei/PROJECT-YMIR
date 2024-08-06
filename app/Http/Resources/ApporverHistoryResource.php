<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApporverHistoryResource extends JsonResource
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
            "approver_id" => $this->approver_id,
            "approver_name" => $this->approver_name,
            "approved_at" => $this->approved_at,
            "rejected_at" => $this->rejected_at,
            "layer" => $this->layer,
        ];
    }
}
