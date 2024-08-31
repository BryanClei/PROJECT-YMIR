<?php

namespace App\Http\Resources;

use App\Http\Resources\RROrdersResource;
use Illuminate\Http\Resources\Json\JsonResource;

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
            "rr_number" => $this->id,
            "pr_number" => $this->pr_id,
            "po_number" => $this->po_id,
            "tagging_id" => $this->tagging_id,
            "user" => [
                "id" => $this->po_transaction->users->id,
                "name" =>
                    $this->po_transaction->users->first_name .
                    " " .
                    $this->po_transaction->users->middle_name .
                    " " .
                    $this->po_transaction->users->last_name,
            ],
            "request_type" => $this->po_transaction->module_name,
            "description" => $this->po_transaction->po_description,
            "order" => $this->rr_orders,
        ];
    }
}
