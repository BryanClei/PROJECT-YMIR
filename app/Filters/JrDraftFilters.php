<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class JrDraftFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "jr_draft_id",
        "jo_description",
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
        "assets",
        "module_name",
        "total_price",
        "status",
        "description",
        "reason",
        "approver_id",
        "rush",
        "outside_labor",
        "cap_ex",
        "direct_po",
        "helpdesk_id",
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
