<?php

namespace App\Http\Resources;

use App\Http\Resources\JoPoOrderResource;
use App\Http\Resources\JobOrderHistoryResource;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\LogHistory\LogHistoryResource;

class JoPoResource extends JsonResource
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
            "jo_year_number_id" => $this->jo_transaction->jo_year_number_id,
            "po_year_number_id" => $this->po_year_number_id,
            "jo_number" => $this->jo_number,
            "jo_description" => $this->jo_description,
            "date_needed" => $this->date_needed,
            "po_number" => $this->po_number,

            "user" => [
                "user_id" => $this->users->id,
                "prefix_id" => $this->users->prefix_id,
                "id_number" => $this->users->id_number,
                "first_name" => $this->users->first_name,
                "middle_name" => $this->users->middle_name,
                "last_name" => $this->users->last_name,
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
            "supplier_id" => $this->supplier_id,
            "supplier_name" => $this->supplier_name,
            "total_price" => $this->total_price,
            "assets" => $this->assets,
            "module_name" => $this->module_name,
            "supplier" => $this->supplier,
            "status" => $this->status,
            "helpdesk_id" => $this->helpdesk_id,
            "rush" => $this->rush,
            "outside_labor" => $this->outside_labor,
            "direct_po" => $this->direct_po,
            "ship_to" => $this->ship_to,
            "cap_ex" => $this->cap_ex,
            "approved_at" => $this->approved_at,
            "rejected_at" => $this->rejected_at,
            "voided_at" => $this->voided_at,
            "cancelled_at" => $this->cancelled_at,
            "description" => $this->description,
            "pr_date" => $this->jo_transaction->created_at,
            "reason" => $this->reason,
            "created_at" => $this->created_at,
            "order_jo_transaction_id" => $this->id,
            "jo_po_orders" => JoPoOrderResource::collection(
                $this->jo_po_orders
            ),
            "jo_po_approver_history" => JobOrderHistoryResource::collection(
                $this->jo_approver_history
            ),
            "log_history" => LogHistoryResource::collection($this->log_history),
            "jo_transaction" => $this->jo_transaction,
        ];
    }
}
