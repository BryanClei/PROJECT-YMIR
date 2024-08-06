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
                "id" => $this->pr_transaction->users->id,
                "name" =>
                    $this->pr_transaction->users->first_name .
                    " " .
                    $this->pr_transaction->users->middle_name .
                    " " .
                    $this->pr_transaction->users->last_name,
            ],
            "request_type" => $this->pr_transaction->module_name,
            "description" => $this->pr_transaction->pr_description,
            "order" => RROrdersResource::collection($this->rr_orders),
        ];
    }
}
