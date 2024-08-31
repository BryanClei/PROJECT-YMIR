<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class PurchaseAssistantFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "pr_number",
        "pr_description",
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
        "supplier_id",
        "supplier_name",
        "module_name",
    ];

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
                                    ->whereNull("po_at");
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
                    ->whereHas("approver_history", function ($query) {
                        $query->whereNotNull("approved_at");
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
                        $query
                            ->whereHas("order", function ($query) {
                                $query
                                    ->whereNotNull("buyer_id")
                                    ->whereNull("po_at");
                            })
                            ->orWhereHas("po_transaction", function ($query) {
                                $query->where("status", "Return");
                            });
                    });
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
            });
    }
}
