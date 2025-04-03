<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class JobItemsReportsFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [];

    public function status($status)
    {
        $this->builder->whereHas("transaction", function ($subQuery) use (
            $status
        ) {
            $subQuery
                ->when($status == "pending", function ($q) {
                    $q->whereIn("status", ["Pending", "For Approval"])
                        ->whereNull("approved_at")
                        ->whereNull("cancelled_at");
                })
                ->when($status == "approved", function ($q) {
                    $q->where("status", "Approved")->whereNotNull(
                        "approved_at"
                    );
                })
                ->when($status == "cancelled", function ($q) {
                    $q->where("status", "Cancelled")->whereNotNull(
                        "cancelled_at"
                    );
                })
                ->when($status == "rejected", function ($q) {
                    $q->where("status", "Rejected");
                })
                ->when($status == "admin_reports", function ($q) {
                    $q->whereNot("status", "Cancelled");
                });
        });
    }
}
