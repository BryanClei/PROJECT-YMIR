<?php

namespace App\Filters;

use Carbon\Carbon;
use App\Models\VladimirUser;
use Essa\APIToolKit\Filters\QueryFilters;

class RROrdersFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "rr_number",
        "rr_id",
        "pr_id",
        "po_id",
        "item_id",
        "item_code",
        "item_name",
        "shipment_no",
    ];

    protected array $relationSearch = [
        "rr_transaction" => ["rr_year_number_id"],
        "po_transaction" => ["po_year_number_id"],
        "pr_transaction" => ["pr_year_number_id"],
    ];

    // public function from_po_date($f)
    // {
    //     $this->builder->whereHas("po_transaction", function ($query) use ($f) {
    //         $query->whereDate("created_at", ">", $f);
    //     });
    // }

    // public function to_po_date($value)
    // {
    //     $this->builder->whereHas("po_transaction", function ($query) use (
    //         $value
    //     ) {
    //         $query->whereDate("created_at", "<=", $value);
    //     });
    // }

    public function from($from)
    {
        $this->builder->whereDate("delivery_date", ">=", $from);
    }

    public function to($to)
    {
        $this->builder->whereDate("delivery_date", "<=", $to);
    }

    // public function requestor($requestor)
    // {
    //     return $this->builder->whereHas(
    //         "rr_transaction.pr_transaction",
    //         function ($query) use ($requestor) {
    //             $query->where(function ($q) use ($requestor) {
    //                 // Search in vladimir_user if module is Asset
    //                 $q->when(request()->module_name === "Asset", function (
    //                     $subQuery
    //                 ) use ($requestor) {
    //                     $subQuery->whereHas("vladimir_user", function (
    //                         $userQuery
    //                     ) use ($requestor) {
    //                         $userQuery
    //                             ->where("id", $requestor)
    //                             ->orWhere("name", "LIKE", "%{$requestor}%")
    //                             ->orWhere(
    //                                 "employee_id",
    //                                 "LIKE",
    //                                 "%{$requestor}%"
    //                             );
    //                     });
    //                 })
    //                     // Search in regular_user if module is not Asset
    //                     ->when(request()->module_name !== "Asset", function (
    //                         $subQuery
    //                     ) use ($requestor) {
    //                         $subQuery->whereHas("regular_user", function (
    //                             $userQuery
    //                         ) use ($requestor) {
    //                             $userQuery
    //                                 ->where("id", $requestor)
    //                                 ->orWhere(
    //                                     "id_number",
    //                                     "LIKE",
    //                                     "%{$requestor}%"
    //                                 )
    //                                 ->orWhere(
    //                                     "first_name",
    //                                     "LIKE",
    //                                     "%{$requestor}%"
    //                                 )
    //                                 ->orWhere(
    //                                     "middle_name",
    //                                     "LIKE",
    //                                     "%{$requestor}%"
    //                                 )
    //                                 ->orWhere(
    //                                     "last_name",
    //                                     "LIKE",
    //                                     "%{$requestor}%"
    //                                 )
    //                                 ->orWhereRaw(
    //                                     "CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) LIKE ?",
    //                                     ["%{$requestor}%"]
    //                                 );
    //                         });
    //                     });
    //             });
    //         }
    //     );
    // }
}
