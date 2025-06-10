<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class JobOrderTransactionFiltersPA extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "jo_number",
        "jo_year_number_id",
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
        "module_name",
        "status",
        "layer",
        "description",
        "reason",
        "asset",
    ];

    public function search_business_unit($search_business_unit, $status = null)
    {
        $this->builder->where(function ($query) use ($search_business_unit) {
            $query
                ->where(
                    "business_unit_name",
                    "like",
                    "%" . $search_business_unit . "%"
                )
                ->orWhere(
                    "business_unit_id",
                    "like",
                    "%" . $search_business_unit . "%"
                );
        });

        // Add status filter if provided
        if ($status !== null) {
            $this->builder->where("status", $status);
        }

        return $this->builder;
    }

    public function status($status)
    {
        $this->builder
            ->when($status === "for_po", function ($query) {
                $query
                    ->with("order", function ($query) {
                        $query->whereNull("po_at");
                    })
                    ->whereHas("order", function ($query) {
                        $query->whereNull("po_at");
                    })
                    ->where("status", "Approved")
                    ->whereNotNull("approved_at")
                    ->whereNull("cancelled_at")
                    ->whereNull("rejected_at")
                    ->whereNull("voided_at")
                    ->whereHas("approver_history", function ($query) {
                        $query->whereNotNull("approved_at");
                    });
            })
            ->when($status === "pending", function ($query) {
                $query
                    ->where("status", "Pending")
                    ->whereNull("cancelled_at")
                    ->whereNull("rejected_at");
            })
            ->when($status === "cancel", function ($query) {
                $query->whereNotNull("cancelled_at")->whereNull("approved_at");
            })
            ->when($status === "voided", function ($query) {
                $query->whereNotNull("voided_at");
            })
            ->when($status === "rejected", function ($query) {
                $query->whereNotNull("rejected_at");
            })

            ->when($status === "approved", function ($query) {
                $query
                    ->where("status", "Approved")
                    ->whereNull("cancelled_at")
                    ->whereNull("voided_at")
                    ->whereHas("approver_history", function ($query) {
                        $query->whereNotNull("approved_at");
                    })
                    ->with([
                        "jo_po_transaction" => function ($query) {
                            $query->where("status", "Approved");
                        },
                        "jo_approver_history" => function ($query) {
                            $query->whereNotNull("approved_at");
                        },
                    ]);
            });
    }
}
