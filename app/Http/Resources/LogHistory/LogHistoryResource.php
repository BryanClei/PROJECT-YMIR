<?php

namespace App\Http\Resources\LogHistory;

use Illuminate\Http\Resources\Json\JsonResource;

class LogHistoryResource extends JsonResource
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
            "histor_id" => $this->id,
            "activity" => $this->activity,
            "pr_id" => $this->pr_id,
            "po_id" => $this->po_id,
            "jo_id" => $this->jo_id,
            "jo_po_id" => $this->jo_po_id,
            "action_by" => [
                "id" => $this->users->id,
                "firstname" => $this->users->first_name,
                "middlename" => $this->users->middle_name,
                "lastname" => $this->users->last_name,
            ],
            "created_at" => $this->created_at,
        ];
    }
}
