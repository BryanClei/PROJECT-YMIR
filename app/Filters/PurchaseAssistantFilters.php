<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class PurchaseAssistantFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "pr_year_number_id",
        "pr_number",
        "pr_description",
        "date_needed",
        "user_id",
        "type_name",
        "business_unit_name",
        "company_name",
        "department_name",
        "department_unit_name",
        "location_name",
        "sub_unit_name",
        "account_title_name",
        "supplier_id",
        "supplier_name",
        "module_name",
    ];

    protected array $relationSearch = [
        "users" => ["user_id", "first_name", "middle_name", "last_name"],
    ];

    // protected function processSearch($search)
    // {
    //     // Join the required relationships first
    //     foreach ($this->relationSearch as $relation => $columns) {
    //         $this->builder->leftJoin(
    //             $relation,
    //             "pr_transactions.user_id",
    //             "=",
    //             $relation . ".id"
    //         );
    //     }

    //     $this->builder->where(function ($query) use ($search) {
    //         // Search in main table columns
    //         foreach ($this->columnSearch as $column) {
    //             $query->orWhere(
    //                 "pr_transactions." . $column,
    //                 "like",
    //                 "%{$search}%"
    //             );
    //         }

    //         // Search in relationship columns
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
            ->when($status === "to_po", function ($query) {
                $query
                    ->where("status", "Approved")
                    ->whereHas("order", function ($query) {
                        $query
                            ->where(function ($query) {
                                $query
                                    ->whereNull("buyer_id")
                                    ->whereNull("po_at")
                                    ->where(
                                        "quantity",
                                        ">",
                                        "partial_received"
                                    );
                            })
                            ->whereNull("po_at");
                    })
                    ->with([
                        "order" => function ($query) {
                            $query->whereNull("buyer_id");
                        },
                    ])
                    ->whereNull("for_po_only")
                    ->whereNull("rejected_at")
                    ->whereNull("voided_at")
                    ->whereNull("cancelled_at")
                    ->where(function ($query) {
                        $query
                            ->where("module_name", "!=", "Asset")
                            ->orWhere(function ($query) {
                                $query
                                    ->where("module_name", "Asset")
                                    ->where(function ($query) {
                                        $query
                                            ->whereHas(
                                                "approver_history",
                                                function ($query) {
                                                    $query->whereNotNull(
                                                        "approved_at"
                                                    );
                                                }
                                            )
                                            ->orWhereDoesntHave(
                                                "approver_history"
                                            );
                                    });
                            });
                    });
            })
            ->when($status === "for_po", function ($query) {
                $query
                    ->where("status", "Approved")
                    ->with([
                        "order" => function ($query) {
                            $query
                                ->whereNull("buyer_id")
                                ->whereNull("supplier_id");
                        },
                    ])
                    ->whereHas("order", function ($query) {
                        $query->whereNull("buyer_id")->whereNull("supplier_id");
                    })
                    ->whereNotNull("for_po_only")
                    ->whereNull("rejected_at")
                    ->whereNull("voided_at")
                    ->whereNull("cancelled_at");
            })
            ->when($status === "pending", function ($query) {
                $query->with("po_transaction", function ($query) {
                    $query
                        ->whereIn("status", ["Pending", "For Approval"])
                        ->whereNull("deleted_at")
                        ->whereNull("cancelled_at");
                });
            })
            ->when($status === "approved", function ($query) {
                $query
                    ->with([
                        "po_transaction" => function ($query) {
                            $query
                                ->whereNull("rejected_at")
                                ->whereNull("voided_at")
                                ->whereNull("cancelled_at")
                                ->where("status", "For Receiving");
                        },
                    ])
                    ->whereNull("rejected_at")
                    ->whereNull("voided_at")
                    ->whereNull("cancelled_at");
            })
            ->when($status === "rejected", function ($query) {
                $query
                    ->with([
                        "po_transaction" => function ($query) {
                            $query->whereNotNull("rejected_at");
                        },
                    ])
                    ->where(function ($query) {
                        $query
                            ->where(function ($subQuery) {
                                $subQuery
                                    ->whereHas("po_transaction", function (
                                        $poQuery
                                    ) {
                                        $poQuery
                                            ->where(
                                                "module_name",
                                                "!=",
                                                "Asset"
                                            )
                                            ->where("status", "Reject")
                                            ->whereNotNull("rejected_at");
                                    })
                                    ->whereHas("order", function ($orderQuery) {
                                        $orderQuery->whereNotNull("buyer_id");
                                    });
                            })
                            ->orWhere(function ($subQuery) {
                                $subQuery
                                    ->whereHas("po_transaction", function (
                                        $poQuery
                                    ) {
                                        $poQuery
                                            ->where("module_name", "Asset")
                                            ->where("status", "Reject")
                                            ->whereNotNull("rejected_at");
                                    })
                                    ->whereDoesntHave("order", function (
                                        $orderQuery
                                    ) {
                                        $orderQuery->whereNotNull("buyer_id");
                                    });
                            });
                    });
            })
            ->when($status === "tagged_buyer", function ($query) {
                $query
                    ->with([
                        "order" => function ($query) {
                            $query
                                ->whereNotNull("buyer_id")
                                ->whereNull("po_at");
                        },
                    ])
                    ->where(function ($query) {
                        $query->whereHas("order", function ($query) {
                            $query
                                ->whereNotNull("buyer_id")
                                ->whereNull("po_at");
                        });
                    })
                    ->where("status", "Approved");
            })

            ->when($status === "return_po", function ($query) {
                $query
                    ->whereHas("order", function ($query) {
                        $query->whereNull("buyer_id");
                    })
                    ->whereHas("po_transaction", function ($query) {
                        $query->where("status", "Return");
                    })
                    ->with([
                        "po_transaction" => function ($query) {
                            $query->where("status", "Return");
                        },
                        "order" => function ($query) {
                            $query->whereNull("buyer_id");
                        },
                    ]);
            })
            ->when($status === "cancelled", function ($query) {
                $query->with("po_transaction", function ($query) {
                    $query
                        ->where("status", "Cancelled")
                        ->whereNotNull("cancelled_at")
                        ->with([
                            "order" => function ($query) {
                                $query->withTrashed();
                            },
                        ]);
                });
            })
            ->when($status === "view_all", function ($query) {
                $query->whereDoesntHave("po_transaction");
            });
    }
}
