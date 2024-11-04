<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class IntegrationGLResource extends JsonResource
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
            "syncId" => $this->id,
            "mark1" => null,
            "mark2" => null,
            "assetCIP" => $this->rr_transaction->po_transaction->asset,
            "accountingTag" => null,
            "transactionDate" => $this->rr_date,
            "clientSupplier" =>
                $this->rr_transaction->po_transaction->supplier_name,
            "accountTitleCode" =>
                $this->rr_transaction->po_transaction->account_title->code,
            "accountTitle" =>
                $this->rr_transaction->po_transaction->account_title->name,
            "companyCode" =>
                $this->rr_transaction->po_transaction->company->code,
            "company" => $this->rr_transaction->po_transaction->company->name,
            "divisionCode" => null,
            "division" => null,
            "departmentCode" =>
                $this->rr_transaction->po_transaction->department->code,
            "department" =>
                $this->rr_transaction->po_transaction->department->name,
            "unitCode" =>
                $this->rr_transaction->po_transaction->department_unit->code,
            "unit" =>
                $this->rr_transaction->po_transaction->department_unit->name,
            "subUnitCode" =>
                $this->rr_transaction->po_transaction->sub_unit->code,
            "subUnit" => $this->rr_transaction->po_transaction->sub_unit->name,
            "locationCode" =>
                $this->rr_transaction->po_transaction->location->code,
            "location" => $this->rr_transaction->po_transaction->location->name,
            "poNumber" =>
                $this->rr_transaction->po_transaction->po_year_number_id,
            "referenceNo" => $this->shipment_no,
            "itemCode" => $this->item_code,
            "itemDescription" => $this->item_name,
            "quantity" => $this->quantity_receive,
            "uom" => $this->order->uom->name,
            "unitPrice" => $this->order->price,
            "lineAmount" => $this->quantity_receive * $this->order->price,
            "voucherJournal" => null,
            "accountType" =>
                $this->rr_transaction->po_transaction->account_title
                    ->account_type->name,
            "drcp" => null,
            "assetCode" => $this->rr_transaction->po_transaction->asset_code,
            "asset" => $this->rr_transaction->po_transaction->asset,
            "serviceProviderCode" => $this->order->supplier->code,
            "serviceProvider" => $this->order->supplier->name,
            "boa" => null,
            "allocation" => null,
            "accountGroup" =>
                $this->rr_transaction->po_transaction->account_title
                    ->account_group->name,
            "accountSubGroup" =>
                $this->rr_transaction->po_transaction->account_title
                    ->account_sub_group->name,
            "financialStatement" =>
                $this->rr_transaction->po_transaction->account_title
                    ->financial_statement->name,
            "unitResponsible" => null,
            "batch" => null,
            "remarks" => null,
            "payrollPeriod" => null,
            "position" => null,
            "payrollType" => null,
            "payrollType2" => null,
            "depreciationDescription" => null,
            "remainingDepreciationValue" => null,
            "usefulLife" => null,
            "month" => null,
            "year" => null,
            "particulars" => null,
            "month2" => null,
            "farmType" => null,
            "jeanRemarks" => null,
            "from" => null,
            "changeTo" => null,
            "reason" => $this->rr_transaction->reason,
            "checkingRemarks" => null,
            "boA2" => null,
            "system" => "YMIR",
            "books" => "Purchases",
        ];
    }
}
