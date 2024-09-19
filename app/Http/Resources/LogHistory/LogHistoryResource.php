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
            "po_year_number_id" =>
                $this->po_transaction->po_year_number_id ?? null,
            "rr_id" => $this->rr_id,
            "rr_year_number_id" =>
                $this->rr_transaction->rr_year_number_id ?? null,
            "jo_id" => $this->jo_id,
            "jo_year_number_id" =>
                $this->jo_transaction->jo_year_number_id ?? null,
            "jo_po_id" => $this->jo_po_id,
            "po_year_number_id" =>
                $this->jo_po_transaction->po_year_number_id ?? null,
            "action" => $this->users
                ? [
                    "id" => $this->users->id,
                    "firstname" => $this->users->first_name,
                    "middlename" => $this->users->middle_name,
                    "lastname" => $this->users->last_name,
                ]
                : null,
            "created_at" => $this->created_at,
        ];
    }
}
