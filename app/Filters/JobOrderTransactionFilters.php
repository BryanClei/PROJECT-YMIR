<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class JobOrderTransactionFilters extends QueryFilters
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
        $user_id = Auth()->user()->id;

        $this->builder
            ->when($status === "pending", function ($query) use ($user_id) {
                $query
                    ->where("user_id", $user_id)
                    ->where("status", "Pending")
                    ->orWhere("status", "For Approval")
                    ->whereNull("approved_at")
                    ->whereNull("cancelled_at")
                    ->whereNull("voided_at");
            })
            ->when($status === "cancelled", function ($query) use ($user_id) {
                $query
                    ->with([
                        "order" => function ($query) {
                            $query->withTrashed();
                        },
                    ])
                    ->withTrashed()
                    ->whereNotNull("cancelled_at")
                    ->whereNull("approved_at")
                    ->where("user_id", $user_id);
            })
            ->when($status === "voided", function ($query) use ($user_id) {
                $query
                    ->whereNotNull("voided_at")
                    ->whereNull("approved_at")
                    ->whereNull("cancelled_at")
                    ->where("user_id", $user_id);
            })
            ->when($status === "rejected", function ($query) use ($user_id) {
                $query
                    ->whereNotNull("rejected_at")
                    ->whereNull("cancelled_at")
                    ->where("user_id", $user_id);
            })
            ->when($status === "approved", function ($query) use ($user_id) {
                $query
                    ->where("user_id", $user_id)
                    ->whereNotNull("approved_at")
                    ->whereNull("cancelled_at")
                    ->whereNull("voided_at")
                    ->where(function ($query) {
                        $query
                            ->whereNotNull("direct_po")
                            ->orWhere(function ($q) {
                                $q->whereNull("direct_po")->whereHas(
                                    "approver_history",
                                    function ($subQuery) {
                                        $subQuery->whereNotNull("approved_at");
                                    }
                                );
                            });
                    });
            })
            ->when($status === "jo_approved", function ($query) use ($user_id) {
                $query
                    ->where("status", "Approved")
                    ->whereNull("cancelled_at")
                    ->whereNull("voided_at")
                    ->whereHas("approver_history", function ($query) {
                        $query->whereNotNull("approved_at");
                    });
            })
            ->when($status === "report_approved_user", function ($query) use (
                $user_id
            ) {
                $query
                    ->where("user_id", $user_id)
                    ->where("status", "Approved")
                    ->whereNotNull("approved_at");
            });
    }
}
