<?php

namespace App\Http\Resources;

use App\Http\Resources\JoPoResource;
use App\Http\Resources\JobOrderResource;
use App\Http\Resources\JORROrderResource;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\LogHistory\LogHistoryResource;

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
            "po_year_number_id" => $this->jo_po_transactions->po_year_number_id,
            "jo_rr_year_number_id" => $this->jo_rr_year_number_id,
            "rr_number" => $this->id,
            "jr_number" => $this->jo_id,
            "jo_number" => $this->jo_po_id,
            "received_by" => $this->received_by,
            "tagging_id" => $this->tagging_id,
            "transaction_date" => $this->transaction_date,
            "user" => $this->jo_po_transactions->users
                ? [
                    "id" => $this->jo_po_transactions->users->id,
                    "name" =>
                        $this->jo_po_transactions->users->first_name .
                        " " .
                        $this->jo_po_transactions->users->middle_name .
                        " " .
                        $this->jo_po_transactions->users->last_name,
                ]
                : null,
            "request_type" => $this->jo_po_transactions->module_name,
            "description" => $this->jo_po_transactions->po_description,
            "rr_orders" => JORROrderResource::collection($this->rr_orders),
            "log_history" => LogHistoryResource::collection($this->log_history),
            "jo_po_transaction" => new JoPoResource($this->jo_po_transactions),
            "jr_order" => new JobOrderResource($this->jr_order),
            "deleted_at" => $this->deleted_at,
            "updated_at" => $this->updated_at,
            "created_at" => $this->created_at,
        ];
        // return [
        //     "po_year_number_id" => $this->po_transaction->po_year_number_id,
        //     "rr_year_number_id" => $this->rr_year_number_id,
        //     "rr_number" => $this->id,
        //     "pr_number" => $this->pr_id,
        //     "po_number" => $this->po_id,
        //     "tagging_id" => $this->tagging_id,
        //     "transaction_date" => $this->transaction_date,
        //     "user" => $this->po_transaction->users
        //         ? [
        //             "id" => $this->po_transaction->users->id,
        //             "name" =>
        //                 $this->po_transaction->users->first_name .
        //                 " " .
        //                 $this->po_transaction->users->middle_name .
        //                 " " .
        //                 $this->po_transaction->users->last_name,
        //         ]
        //         : null,
        //     "request_type" => $this->po_transaction->module_name,
        //     "description" => $this->po_transaction->po_description,
        //     "order" => $this->rr_orders,
        //     "log_history" => LogHistoryResource::collection($this->log_history),
        //     "deleted_at" => $this->deleted_at,
        // ];
    }
}
