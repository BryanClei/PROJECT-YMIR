<?php

namespace App\Http\Resources;

use App\Http\Resources\AssetsResource;
use App\Http\Resources\PRItemsResource;
use App\Http\Resources\ApporverHistoryResource;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\LogHistory\LogHistoryResource;

class PRTransactionResource extends JsonResource
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
            "pr_year_number_id" => $this->pr_year_number_id,
            "pr_number" => $this->pr_number,
            "transaction_no" => $this->transaction_no,
            "pr_description" => $this->pr_description,
            "helpdesk_id" => $this->helpdesk_id,
            "date_needed" => $this->date_needed,

            "user" => [
                "prefix_id" => $this->users->prefix_id,
                "id_number" => $this->users->id_number,
                "first_name" => $this->users->first_name,
                "middle_name" => $this->users->middle_name,
                "last_name" => $this->users->last_name,
                "mobile_no" => $this->users->mobile_no,
            ],

            "type" => [
                "id" => $this->type_id,
                "name" => $this->type_name,
            ],
            "businessUnit" => [
                "id" => $this->business_unit_id,
                "name" => $this->business_unit_name,
            ],
            "company" => [
                "id" => $this->company_id,
                "name" => $this->company_name,
            ],
            "department" => [
                "id" => $this->department_id,
                "name" => $this->department_name,
            ],
            "departmentUnit" => [
                "id" => $this->department_unit_id,
                "name" => $this->department_unit_name,
            ],
            "location" => [
                "id" => $this->location_id,
                "name" => $this->location_name,
            ],
            "subUnit" => [
                "id" => $this->sub_unit_id,
                "name" => $this->sub_unit_name,
            ],
            "accountTitle" => [
                "id" => $this->account_title_id,
                "name" => $this->account_title_name,
            ],
            "sgp" => $this->sgp,
            "f1" => $this->f1,
            "f2" => $this->f2,
            "for_po_only" => $this->for_po_only,
            "for_po_only_id" => $this->for_po_only_id,
            "for_marketing" => $this->for_marketing,
            "module_name" => $this->module_name,
            "status" => $this->status,
            "approved_at" => $this->approved_at,
            "rejected_at" => $this->rejected_at,
            "voided_at" => $this->voided_at,
            "cancelled_at" => $this->cancelled_at,
            "description" => $this->description,
            "reason" => $this->reason,
            "created_at" => $this->created_at,
            "order_transaction_id" => $this->id,
            "order" => PRItemsResource::collection($this->order),
            "approver_history" => ApporverHistoryResource::collection(
                $this->approver_history
            ),
            "log_history" => LogHistoryResource::collection($this->log_history),
            "po_transaction" => PoResource::collection($this->po_transaction),
        ];
    }
}
