<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class JobItemsFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [];

    public function status($status)
    {
        $user_id = Auth()->user()->id;

        $this->builder
            ->when($status === "report_approved_user", function ($query) use (
                $user_id
            ) {
                $query->whereHas("transaction", function ($subQuery) use (
                    $user_id
                ) {
                    $subQuery
                        ->where("user_id", $user_id)
                        ->where("status", "Approved")
                        ->whereNotNull("approved_at")
                        ->whereNull("rejected_at")
                        ->whereNull("cancelled_at");
                });
            })
            ->when($status === "report_approved", function ($query) {
                $query->whereHas("transaction", function ($subQuery) {
                    $subQuery
                        ->where("status", "Approved")
                        ->whereNotNull("approved_at")
                        ->whereNull("rejected_at")
                        ->whereNull("cancelled_at");
                });
            })
            ->when($status === "admin_reports", function ($query) {
                $query->whereHas("transaction", function ($subQuery) {
                    $subQuery
                        ->where(function ($query) {
                            $query
                                ->where("status", "Pending")
                                ->orWhere("status", "For Approval");
                        })
                        ->whereNull("approved_at")
                        ->whereNull("rejected_at")
                        ->whereNull("cancelled_at");
                });
            });
    }

    public function from($from)
    {
        $this->builder->whereHas("transaction", function ($query) use ($from) {
            $query->whereDate("created_at", ">=", $from);
        });
    }
    public function to($to)
    {
        $this->builder->whereHas("transaction", function ($query) use ($to) {
            $query->whereDate("created_at", "<=", $to);
        });
    }
}
