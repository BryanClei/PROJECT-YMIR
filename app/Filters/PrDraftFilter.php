<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class PrDraftFilter extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "pr_description",
        "date_needed",
        "user_id",
        "type_id",
        "type_name",
        "business_unit_id",
        "business_unit_name",
        "company_id",
        "company_name",
        "department_id",
        "department_name",
        "department_unit_id",
        "department_unit_name",
        "location_id",
        "location_name",
        "sub_unit_id",
        "sub_unit_name",
        "account_title_id",
        "account_title_name",
        "supplier_id",
        "supplier_name",
        "module_name",
        "status",
        "asset_code",
        "cap_ex",
        "helpdesk_id",
        "asset",
        "sgp",
        "f1",
        "f2",
        "rush",
        "place_order",
        "for_po_only",
        "for_po_only_id",
        "for_marketing",
    ];

    public function status($status)
    {
        $this->builder
            ->when($status == "draft", function ($query) {
                $query->where("status", "Draft");
            })
            ->when($status == "submitted", function ($query) {
                $query->where("status", "Submitted");
            });
    }
}
