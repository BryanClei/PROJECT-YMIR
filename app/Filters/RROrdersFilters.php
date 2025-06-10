<?php

namespace App\Filters;

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

    // protected function processSearch($search)
    // {
    //     // Join the required relationships only once with correct conditions
    //     $this->builder
    //         ->leftJoin(
    //             "rr_transaction",
    //             "rr_orders.rr_id",
    //             "=",
    //             "rr_transaction.id"
    //         )
    //         ->leftJoin(
    //             "po_transaction",
    //             "rr_orders.po_id",
    //             "=",
    //             "po_transaction.id"
    //         )
    //         ->leftJoin(
    //             "pr_transaction",
    //             "rr_orders.pr_id",
    //             "=",
    //             "pr_transaction.id"
    //         );

    //     $this->builder->where(function ($query) use ($search) {
    //         foreach ($this->columnSearch as $column) {
    //             $query->orWhere("rr_orders." . $column, "like", "%{$search}%");
    //         }

    //         foreach ($this->relationSearch as $table => $columns) {
    //             foreach ($columns as $column) {
    //                 $query->orWhere(
    //                     $table . "." . $column,
    //                     "like",
    //                     "%{$search}%"
    //                 );
    //             }
    //         }
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
