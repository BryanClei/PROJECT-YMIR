<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class JORRTransactionFilter extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = ["jo_rr_year_number_id", "jo_po_id"];

    protected array $relationSearch = [
        "jo_po_transactions" => ["po_year_number_id"],
    ];

    public function status($status, $date = null)
    {
        $user = Auth()->user()->id;

        $this->builder
            ->when($status === "cancel", function ($query) use ($user) {
                $query->whereNotNull("deleted_at")->where("received_by", $user);
            })
            ->when($status === "received", function ($query) use ($user) {
                $query->whereNull("deleted_at")->where("received_by", $user);
            })
            ->when($status === "rr_today", function ($query) use ($date) {
                $date = request("date") ?? date("Y-m-d");
                $query->whereDate("created_at", $date);
            });
    }
}
