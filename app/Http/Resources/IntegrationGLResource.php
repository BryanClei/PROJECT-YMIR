<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class IntegrationGLResource extends JsonResource
{
    /**
     * Transform the resource into a single object with entries.
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
                "syncId" => "Y" . (string) $this->id,
                "mark1" => null,
                "mark2" => null,
                "assetCIP" => $this->po_transaction->asset,
                "accountingTag" => null,
                "transactionDate" => $formatted_del,
                "clientSupplier" => $this->order->supplier->name ?? null,
                "accountTitleCode" =>
                    $this->po_transaction->account_title->code,
                "accountTitle" => $this->po_transaction->account_title->name,
                "companyCode" => $this->po_transaction->company->code,
                "company" => $this->po_transaction->company->name,
                "divisionCode" => $this->po_transaction->business_unit->code,
                "division" => $this->po_transaction->business_unit_name,
                "departmentCode" => $this->po_transaction->department->code,
                "department" => $this->po_transaction->department->name,
                "unitCode" => $this->po_transaction->department_unit->code,
                "unit" => $this->po_transaction->department_unit->name,
                "subUnitCode" => $this->po_transaction->sub_unit->code,
                "subUnit" => $this->po_transaction->sub_unit->name,
                "locationCode" => $this->po_transaction->location->code,
                "location" => $this->po_transaction->location->name,
                "rrNumber" => $this->rr_transaction->rr_year_number_id,
                "poNumber" => $this->po_transaction->po_year_number_id,
                "referenceNo" => $this->shipment_no,
                "itemCode" => $this->order->item_code,
                "itemDescription" => $this->item_name,
                "quantity" => $this->quantity_receive,
                "uom" => $this->order->uom->name,
                "unitPrice" => $this->order->price,
                "lineAmount" => $this->quantity_receive * $this->order->price,
                "voucherJournal" => null,
                "accountType" =>
                    $this->po_transaction->account_title->account_type->name,
                "drcr" => "Debit",
                "assetCode" => $this->po_transaction->asset_code,
                "asset" => $this->po_transaction->asset,
                "serviceProviderCode" => $this->order->supplier->code ?? null,
                "serviceProvider" => $this->order->supplier->name ?? null,
                "boa" => "Purchases Book",
                "allocation" => null,
                "accountGroup" =>
                    $this->po_transaction->account_title->account_group->name,
                "accountSubGroup" =>
                    $this->po_transaction->account_title->account_sub_group
                        ->name,
                "financialStatement" =>
                    $this->po_transaction->account_title->financial_statement
                        ->name,
                "unitResponsible" => null,
                "batch" => null,
                "lineDescription" => $this->shipment_no,
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
                "reason" => $this->rr_transaction->reason,
                "checkingRemarks" => null,
                "boA2" => $this->po_transaction->module_name,
                "system" => "YMIR",
                "books" => "Voucher Payable",
            ],
            [
                "syncId" => "Y" . $this->id . ".1",
                "mark1" => null,
                "mark2" => null,
                "assetCIP" => $this->po_transaction->asset,
                "accountingTag" => null,
                "transactionDate" => $formatted_del,
                "clientSupplier" => $this->po_transaction->supplier_name,
                "accountTitleCode" =>
                    $this->po_transaction->account_title->credit_code ??
                    $this->po_transaction->account_title->code,
                "accountTitle" =>
                    $this->po_transaction->account_title->credit_name ??
                    $this->po_transaction->account_title->name,
                "companyCode" => $this->po_transaction->company->code,
                "company" => $this->po_transaction->company->name,
                "divisionCode" => $this->po_transaction->business_unit->code,
                "division" => $this->po_transaction->business_unit_name,
                "departmentCode" => $this->po_transaction->department->code,
                "department" => $this->po_transaction->department->name,
                "unitCode" => $this->po_transaction->department_unit->code,
                "unit" => $this->po_transaction->department_unit->name,
                "subUnitCode" => $this->po_transaction->sub_unit->code,
                "subUnit" => $this->po_transaction->sub_unit->name,
                "locationCode" => $this->po_transaction->location->code,
                "location" => $this->po_transaction->location->name,
                "rrNumber" => $this->rr_transaction->rr_year_number_id,
                "poNumber" => $this->po_transaction->po_year_number_id,
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
                    $this->po_transaction->account_title->account_type->name,
                "drcr" => "Credit",
                "assetCode" => $this->po_transaction->asset_code,
                "asset" => $this->po_transaction->asset ?? null,
                "serviceProviderCode" => $this->order->supplier->code ?? null,
                "serviceProvider" => $this->order->supplier->name ?? null,
                "boa" => "Purchases Book",
                "allocation" => null,
                "accountGroup" =>
                    $this->po_transaction->account_title->account_group->name,
                "accountSubGroup" =>
                    $this->po_transaction->account_title->account_sub_group
                        ->name,
                "financialStatement" =>
                    $this->po_transaction->account_title->financial_statement
                        ->name,
                "unitResponsible" => null,
                "batch" => null,
                "lineDescription" => $this->shipment_no,
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
                "reason" => $this->rr_transaction->reason,
                "checkingRemarks" => null,
                "boA2" => $this->po_transaction->module_name,
                "system" => "YMIR",
                "books" => "Voucher Payable",
            ],
        ];
    }
}
