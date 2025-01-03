<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RRV2OrderResource extends JsonResource
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
            "rr_year_number_id" => $this->rr_year_number_id,
            "rr_number" => $this->id,
            "pr_number" => $this->pr_id,
            "po_number" => $this->po_id,
            "tagging_id" => $this->tagging_id,
            "transaction_date" => $this->transaction_date,
            "rr_orders" => $this->rr_orders,
            "log_history" => LogHistoryResource::collection($this->log_history),
            "deleted_at" => $this->deleted_at,
        ];
    }
}
