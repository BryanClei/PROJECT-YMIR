<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class ApproverFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [];

    // public function status($status)
    // {
    //     $this->builder
    //         ->when($status === "pending", function ($query) {
    //             $query

    //                 ->whereNull("approved_at")
    //                 ->whereNull("rejected_at")
    //                 ->whereNull("voided_at")
    //                 ->whereNull("cancelled_at");
    //         })
    //         ->when($filter === "cancel", function ($query) {
    //             $query->whereNotNull("cancelled_at")->whereNull("approved_at");
    //         })
    //         ->when($filter === "rejected", function ($query) {
    //             $query->whereNotNull("rejected_at")->whereNull("voided_at");
    //         });
    // }
}
