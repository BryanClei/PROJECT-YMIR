<?php

namespace App\Filters;

use App\Models\VladimirUser;
use Essa\APIToolKit\Filters\QueryFilters;

class RROrdersFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [];

    public function from($from)
    {
        $this->builder->whereDate("rr_date", ">=", $from);
    }
    public function to($to)
    {
        $this->builder->whereDate("rr_date", "<=", $to);
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
