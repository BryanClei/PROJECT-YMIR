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
        $formatted_month = Carbon::parse($this->delivery_date)->format("M");
        $year = Carbon::parse($this->delivery_date)->format("Y"); // Year
        $formatted_del = Carbon::parse($this->delivery_date)->format("Y-m-d");
        $month2 = Carbon::parse($this->delivery_date)->format("Ym");

        $assetCIP =
            $this->po_transaction->module_name === "Asset"
                ? $this->order->remarks
                : null;

        $boA2 =
            $this->po_transaction->module_name === "Asset"
                ? "Fixed Asset"
                : $this->po_transaction->module_name;

        $userData = $this->getUserData();

        return [
            [
                "syncId" => "Y" . (string) $this->id,
                "mark1" => null,
                "mark2" => null,
                "assetCIP" => $assetCIP,
                "accountingTag" => null,
                "transactionDate" => $formatted_del,
                "clientSupplier" => $this->order->supplier->name ?? null,
                "accountTitleCode" =>
                    (int) $this->po_transaction->account_title->code,
                "accountTitle" => $this->po_transaction->account_title->name,
                "companyCode" => (int) $this->po_transaction->company->code,
                "company" => $this->po_transaction->company->name,
                "divisionCode" =>
                    (int) $this->po_transaction->business_unit->code,
                "division" => $this->po_transaction->business_unit_name,
                "departmentCode" =>
                    (int) $this->po_transaction->department->code,
                "department" => $this->po_transaction->department->name,
                "unitCode" =>
                    (int) $this->po_transaction->department_unit->code,
                "unit" => $this->po_transaction->department_unit->name,
                "subUnitCode" => (int) $this->po_transaction->sub_unit->code,
                "subUnit" => $this->po_transaction->sub_unit->name,
                "locationCode" => (int) $this->po_transaction->location->code,
                "location" => $this->po_transaction->location->name,
                "poNumber" => $this->po_transaction->po_year_number_id,
                "rrNumber" => $this->rr_transaction->rr_year_number_id,
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
                "serviceProviderCode" =>
                    $this->po_transaction->module_name === "Asset"
                        ? $userData["employee_id"] ?? null
                        : $userData["prefix_id"] .
                                "-" .
                                $userData["id_number"] ??
                            null,
                "serviceProvider" =>
                    $this->po_transaction->module_name === "Asset"
                        ? (isset($userData["first_name"])
                            ? $userData["first_name"] .
                                " " .
                                $userData["last_name"]
                            : null)
                        : (isset($userData["first_name"])
                            ? $userData["first_name"] .
                                " " .
                                $userData["last_name"]
                            : null),
                "boa" => $boA2,
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
                "remarks" => null,
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
                "month2" => $month2,
                "farmType" => null,
                "adjustment" => null,
                "from" => null,
                "changeTo" => null,
                "reason" => $this->rr_transaction->reason,
                "checkingRemarks" => null,
                "bankName" => null,
                "chequeNumber" => null,
                "chequeVoucherNumber" => null,
                "chequeDate" => null,
                "releasedDate" => null,
                "boA2" => $boA2,
                "system" => "YMIR",
                "books" => "Purchases Requisition Book",
            ],
            [
                "syncId" => "Y" . $this->id . ".1",
                "mark1" => null,
                "mark2" => null,
                "assetCIP" => $assetCIP,
                "accountingTag" => null,
                "transactionDate" => $formatted_del,
                "clientSupplier" => $this->po_transaction->supplier_name,
                "accountTitleCode" =>
                    (int) $this->po_transaction->account_title->credit_code ??
                    (int) $this->po_transaction->account_title->code,
                "accountTitle" =>
                    $this->po_transaction->account_title->credit_name ??
                    $this->po_transaction->account_title->name,
                "companyCode" => (int) $this->po_transaction->company->code,
                "company" => $this->po_transaction->company->name,
                "divisionCode" =>
                    (int) $this->po_transaction->business_unit->code,
                "division" => $this->po_transaction->business_unit_name,
                "departmentCode" =>
                    (int) $this->po_transaction->department->code,
                "department" => $this->po_transaction->department->name,
                "unitCode" =>
                    (int) $this->po_transaction->department_unit->code,
                "unit" => $this->po_transaction->department_unit->name,
                "subUnitCode" => (int) $this->po_transaction->sub_unit->code,
                "subUnit" => $this->po_transaction->sub_unit->name,
                "locationCode" => (int) $this->po_transaction->location->code,
                "location" => $this->po_transaction->location->name,
                "poNumber" => $this->po_transaction->po_year_number_id,
                "rrNumber" => $this->rr_transaction->rr_year_number_id,
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
                "assetCode" => (int) $this->po_transaction->asset_code,
                "asset" => $this->po_transaction->asset ?? null,
                "serviceProviderCode" =>
                    $this->po_transaction->module_name === "Asset"
                        ? $userData["employee_id"] ?? null
                        : $userData["prefix_id"] .
                                "-" .
                                $userData["id_number"] ??
                            null,
                "serviceProvider" =>
                    $this->po_transaction->module_name === "Asset"
                        ? (isset($userData["first_name"])
                            ? $userData["first_name"] .
                                " " .
                                $userData["last_name"]
                            : null)
                        : (isset($userData["first_name"])
                            ? $userData["first_name"] .
                                " " .
                                $userData["last_name"]
                            : null),
                "boa" => $boA2,
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
                "remarks" => null,
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
                "month2" => $month2,
                "farmType" => null,
                "adjustment" => null,
                "from" => null,
                "changeTo" => null,
                "reason" => $this->rr_transaction->reason,
                "checkingRemarks" => null,
                "bankName" => null,
                "chequeNumber" => null,
                "chequeVoucherNumber" => null,
                "chequeDate" => null,
                "releasedDate" => null,
                "boA2" => $boA2,
                "system" => "YMIR",
                "books" => "Purchases Requisition Book",
            ],
        ];
    }

    protected function getUserData()
    {
        // If it's an Asset module, try to get Vladimir user
        if ($this->po_transaction->module_name === "Asset") {
            if ($this->po_transaction->vladimir_user) {
                return [
                    "id" => $this->po_transaction->vladimir_user->id,
                    "employee_id" =>
                        $this->po_transaction->vladimir_user->employee_id,
                    "first_name" =>
                        $this->po_transaction->vladimir_user->firstname,
                    "last_name" =>
                        $this->po_transaction->vladimir_user->lastname,
                ];
            }
        } else {
            // For non-Asset modules, use regular user
            if ($this->po_transaction->regular_user) {
                return [
                    "prefix_id" =>
                        $this->po_transaction->regular_user->prefix_id,
                    "id_number" =>
                        $this->po_transaction->regular_user->id_number,
                    "first_name" =>
                        $this->po_transaction->regular_user->first_name,
                    "middle_name" =>
                        $this->po_transaction->regular_user->middle_name,
                    "last_name" =>
                        $this->po_transaction->regular_user->last_name,
                ];
            }
        }

        // Return empty array if no user found
        return [];
    }
}
