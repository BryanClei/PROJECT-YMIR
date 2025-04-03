<?php

namespace App\Http\Resources;

use App\Http\Resources\RROrdersResource;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\LogHistory\LogHistoryResource;

class RRResource extends JsonResource
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
            // "po_year_number_id" => $this->po_transaction->po_year_number_id,
            "rr_year_number_id" => $this->rr_year_number_id,
            "rr_number" => $this->id,
            "pr_number" => $this->pr_id,
            "po_number" => $this->po_id,
            "tagging_id" => $this->tagging_id,
            "transaction_date" => $this->transaction_date,
            "received_by" => $this->users
                ? [
                    "id" => $this->users->id,
                    "name" =>
                        $this->users->first_name .
                        " " .
                        $this->users->middle_name .
                        " " .
                        $this->users->last_name,
                ]
                : null,
            // "request_type" => $this->po_transaction->module_name,
            // "description" => $this->po_transaction->po_description,
            "order" => $this->rr_orders,
            "log_history" => LogHistoryResource::collection($this->log_history),
            "deleted_at" => $this->deleted_at,
        ];
    }
}
