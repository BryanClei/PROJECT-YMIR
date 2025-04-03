<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class IntegrationGLJORRResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $formatted_month = Carbon::parse($this->delivery_date)->format("M"); // Three-letter month
        $year = Carbon::parse($this->delivery_date)->format("Y"); // Year
        $formatted_del = Carbon::parse($this->delivery_date)->format("Y-m-d");

        return [
            [
                "syncId" => "YJO" . (string) $this->id,
                "mark1" => null,
                "mark2" => null,
                "assetCIP" => $this->jo_po_transaction->asset,
                "accountingTag" => null,
                "transactionDate" => $formatted_del,
                "clientSupplier" => $this->order->supplier->name ?? null,
                "accountTitleCode" =>
                    $this->jo_po_transaction->account_title->code,
                "accountTitle" => $this->jo_po_transaction->account_title->name,
                "companyCode" => $this->jo_po_transaction->company->code,
                "company" => $this->jo_po_transaction->company->name,
                "divisionCode" => $this->jo_po_transaction->business_unit->code,
                "division" => $this->jo_po_transaction->business_unit_name,
                "departmentCode" => $this->jo_po_transaction->department->code,
                "department" => $this->jo_po_transaction->department->name,
                "unitCode" => $this->jo_po_transaction->department_unit->code,
                "unit" => $this->jo_po_transaction->department_unit->name,
                "subUnitCode" => $this->jo_po_transaction->sub_unit->code,
                "subUnit" => $this->jo_po_transaction->sub_unit->name,
                "locationCode" => $this->jo_po_transaction->location->code,
                "location" => $this->jo_po_transaction->location->name,
                "rrNumber" => $this->jo_rr_transaction->rr_year_number_id,
                "poNumber" => $this->jo_po_transaction->po_year_number_id,
                "referenceNo" => $this->shipment_no,
                "itemCode" => $this->order->item_code,
                "itemDescription" => $this->item_name,
                "quantity" => $this->quantity_receive,
                "uom" => $this->order->uom->name,
                "unitPrice" => $this->order->price,
                "lineAmount" => $this->quantity_receive * $this->order->price,
                "voucherJournal" => null,
                "accountType" =>
                    $this->jo_po_transaction->account_title->account_type->name,
                "drcr" => "Debit",
                "assetCode" => $this->jo_po_transaction->asset_code,
                "asset" => $this->jo_po_transaction->asset,
                "serviceProviderCode" => $this->order->supplier->code ?? null,
                "serviceProvider" => $this->order->supplier->name ?? null,
                "boa" => "Purchases Book",
                "allocation" => null,
                "accountGroup" =>
                    $this->jo_po_transaction->account_title->account_group
                        ->name,
                "accountSubGroup" =>
                    $this->jo_po_transaction->account_title->account_sub_group
                        ->name,
                "financialStatement" =>
                    $this->jo_po_transaction->account_title->financial_statement
                        ->name,
                "unitResponsible" => null,
                "batch" => null,
                "remarks" => $this->shipment_no,
                "payrollPeriod" => null,
                "position" => null,
                "payrollType" => null,
                "payrollType2" => null,
                "depreciationDescription" => null,
                "remainingDepreciationValue" => null,
                "usefulLife" => null,
                "month" => $formatted_month,
                "year" => $year,
                "particulars" => null,
                "month2" => null,
                "farmType" => null,
                "jeanRemarks" => null,
                "from" => null,
                "changeTo" => null,
                "reason" => $this->jo_rr_transaction->reason,
                "checkingRemarks" => null,
                "boA2" => $this->jo_po_transaction->module_name,
                "system" => "YMIR",
                "books" => "Purchases Book",
            ],
            [
                "syncId" => "YJO" . $this->id . ".1",
                "mark1" => null,
                "mark2" => null,
                "assetCIP" => $this->jo_po_transaction->asset,
                "accountingTag" => null,
                "transactionDate" => $formatted_del,
                "clientSupplier" => $this->jo_po_transaction->supplier_name,
                "accountTitleCode" =>
                    $this->jo_po_transaction->account_title->credit_code ??
                    $this->jo_po_transaction->account_title->code,
                "accountTitle" =>
                    $this->jo_po_transaction->account_title->credit_name ??
                    $this->jo_po_transaction->account_title->name,
                "companyCode" => $this->jo_po_transaction->company->code,
                "company" => $this->jo_po_transaction->company->name,
                "divisionCode" => $this->jo_po_transaction->business_unit->code,
                "division" => $this->jo_po_transaction->business_unit_name,
                "departmentCode" => $this->jo_po_transaction->department->code,
                "department" => $this->jo_po_transaction->department->name,
                "unitCode" => $this->jo_po_transaction->department_unit->code,
                "unit" => $this->jo_po_transaction->department_unit->name,
                "subUnitCode" => $this->jo_po_transaction->sub_unit->code,
                "subUnit" => $this->jo_po_transaction->sub_unit->name,
                "locationCode" => $this->jo_po_transaction->location->code,
                "location" => $this->jo_po_transaction->location->name,
                "rrNumber" => $this->jo_rr_transaction->rr_year_number_id,
                "poNumber" => $this->jo_po_transaction->po_year_number_id,
                "referenceNo" => $this->shipment_no,
                "itemCode" => $this->order->item_code,
                "itemDescription" => $this->item_name,
                "quantity" => $this->quantity_receive,
                "uom" => $this->order->uom->name,
                "unitPrice" => $this->order->price,
                "lineAmount" => -(
                    $this->quantity_receive * $this->order->price
                ),
                "voucherJournal" => null,
                "accountType" =>
                    $this->jo_po_transaction->account_title->account_type->name,
                "drcr" => "Credit",
                "assetCode" => $this->jo_po_transaction->asset_code,
                "asset" => $this->jo_po_transaction->asset ?? null,
                "serviceProviderCode" => $this->order->supplier->code ?? null,
                "serviceProvider" => $this->order->supplier->name ?? null,
                "boa" => "Purchases Book",
                "allocation" => null,
                "accountGroup" =>
                    $this->jo_po_transaction->account_title->account_group
                        ->name,
                "accountSubGroup" =>
                    $this->jo_po_transaction->account_title->account_sub_group
                        ->name,
                "financialStatement" =>
                    $this->jo_po_transaction->account_title->financial_statement
                        ->name,
                "unitResponsible" => null,
                "batch" => null,
                "remarks" => $this->shipment_no,
                "payrollPeriod" => null,
                "position" => null,
                "payrollType" => null,
                "payrollType2" => null,
                "depreciationDescription" => null,
                "remainingDepreciationValue" => null,
                "usefulLife" => null,
                "month" => $formatted_month,
                "year" => $year,
                "particulars" => null,
                "month2" => null,
                "farmType" => null,
                "jeanRemarks" => null,
                "from" => null,
                "changeTo" => null,
                "reason" => $this->jo_rr_transaction->reason,
                "checkingRemarks" => null,
                "boA2" => $this->jo_po_transaction->module_name,
                "system" => "YMIR",
                "books" => "Purchases Book",
            ],
        ];
    }
}
