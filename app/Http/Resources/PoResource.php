<?php

namespace App\Http\Resources;

use App\Http\Resources\PoItemResource;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\LogHistory\LogHistoryResource;

class PoResource extends JsonResource
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
            "pr_year_number_id" => $this->pr_transaction->pr_year_number_id,
            "po_year_number_id" => $this->po_year_number_id,
            "transaction_no" => $this->pr_transaction->transaction_no ?? null,
            "po_number" => $this->po_number,
            "pr_number" => $this->pr_number,
            "po_description" => $this->po_description,
            "date_needed" => $this->date_needed,
            "po_number" => $this->po_number,

            "user" => $this->getUserData(),

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
            "supplier_id" => [
                "id" => $this->supplier_id,
                "name" => $this->supplier_name,
            ],
            "asset" => [
                "asset" => $this->asset,
                "asset_code" => $this->asset_code,
            ],
            "supplier" => [
                "supplier_id" => $this->supplier_id,
                "supplier_name" => $this->supplier_name,
                "pcf_remarks" => $this->pcf_remarks,
            ],
            "cap_ex" => $this->cap_ex,
            "ship_to" => $this->ship_to,
            "sgp" => $this->sgp,
            "f1" => $this->f1,
            "f2" => $this->f2,
            "rush" => $this->rush,
            "for_po_only" => $this->for_po_only,
            "user_tagging" => $this->user_tagging,
            "place_order" => $this->place_order,
            "module_name" => $this->module_name,
            "approved_at" => $this->approved_at,
            "rejected_at" => $this->rejected_at,
            "voided_at" => $this->voided_at,
            "cancelled_at" => $this->cancelled_at,
            "status" => $this->status,
            "description" => $this->description,
            "reason" => $this->reason,
            "edit_remarks" => $this->edit_remarks,
            "pr_date" => $this->pr_transaction->created_at,
            "created_at" => $this->created_at,
            "deleted_at" => $this->deleted_at,
            "buyer" => $this->order->first()
                ? [
                    "buyer_id" => $this->order->first()->buyer_id,
                    "buyer_name" => $this->order->first()->buyer_name,
                ]
                : null,
            "order" => PoItemResource::collection($this->order),
            "approver_history" => ApporverHistoryResource::collection(
                $this->approver_history
            ),
            "pr_approver_history" => ApporverHistoryResource::collection(
                $this->pr_approver_history
            ),
            "log_history" => LogHistoryResource::collection($this->log_history),
            "rr_transaction" => $this->rr_transaction
                ? RRResource::collection($this->rr_transaction)
                : [],
        ];
    }

    protected function getUserData()
    {
        if ($this->module_name === "Asset") {
            if ($this->vladimir_user) {
                return [
                    "id" => $this->vladimir_user->id,
                    "employee_id" => $this->vladimir_user->employee_id,
                    "username" => $this->vladimir_user->username,
                    "first_name" => strtoupper($this->vladimir_user->firstname),
                    "last_name" => strtoupper($this->vladimir_user->lastname),
                ];
            }
        } else {
            if ($this->regular_user) {
                return [
                    "prefix_id" => $this->regular_user->prefix_id,
                    "id_number" => $this->regular_user->id_number,
                    "first_name" => $this->regular_user->first_name,
                    "middle_name" => $this->regular_user->middle_name,
                    "last_name" => $this->regular_user->last_name,
                    "mobile_no" => $this->regular_user->mobile_no,
                    "warehouse" => $this->when(
                        $this->regular_user->warehouse,
                        fn() => [
                            "warehouse_id" => $this->regular_user->warehouse_id,
                            "warehouse_name" =>
                                $this->regular_user->warehouse->name,
                            "warehouse_code" =>
                                $this->regular_user->warehouse->code,
                        ]
                    ),
                ];
            }
        }
        return [];
    }
}
