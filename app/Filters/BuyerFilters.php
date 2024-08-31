<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class BuyerFilters extends QueryFilters
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
        $user_id = auth()->user()->id;

        $this->builder
            ->when($status === "po_approved", function ($query) {
                $query
                    ->whereNotNull("approved_at")
                    ->whereHas("po_transaction", function ($query) {
                        $query->where("status", "For Receiving");
                    })
                    ->with("po_transaction", function ($query) {
                        $query
                            ->whereNotNull("approved_at")
                            ->whereNull("cancelled_at")
                            ->whereNull("rejected_at");
                    });
            })
            ->when($status === "to_po", function ($query) use ($user_id) {
                $query
                    ->with([
                        "order" => function ($query) use ($user_id) {
                            $query
                                ->where("buyer_id", $user_id)
                                ->whereNull("supplier_id")
                                ->whereNull("po_at");
                        },
                    ])
                    ->whereHas("order", function ($query) use ($user_id) {
                        $query
                            ->where("buyer_id", $user_id)
                            ->whereNull("supplier_id")
                            ->whereNull("po_at");
                    })
                    ->where("status", "Approved")
                    ->whereNotNull("approved_at");
            })
            ->when($status === "approved", function ($query) use ($user_id) {
                $query
                    ->where("status", "Approved")
                    ->whereNotNull("approved_at")
                    ->whereHas("po_transaction", function ($query) {
                        $query->where("status", "Approved");
                    });
            })
            ->when($status === "cancelled", function ($query) use ($user_id) {
                $query
                    ->whereHas("order", function ($query) use ($user_id) {
                        $query->where("buyer_id", $user_id);
                    })
                    ->with([
                        "po_transaction" => function ($query) {
                            $query->where("status", "Cancelled");
                        },
                        "po_transaction.order" => function ($query) {
                            $query->withTrashed();
                        },
                    ])
                    ->whereHas("po_transaction", function ($query) {
                        $query
                            ->where("status", "Cancelled")
                            ->whereNotNull("cancelled_at");
                    });
            })
            ->when($status === "rejected", function ($query) use ($user_id) {
                $query
                    ->with([
                        "po_transaction" => function ($query) use ($user_id) {
                            $query
                                ->where("status", "Reject")
                                ->whereHas("order", function ($subQuery) use (
                                    $user_id
                                ) {
                                    $subQuery->where("buyer_id", $user_id);
                                });
                        },
                    ])
                    ->whereHas("po_transaction", function ($query) use (
                        $user_id
                    ) {
                        $query
                            ->where("status", "Reject")
                            ->whereNotNull("rejected_at")
                            ->whereHas("order", function ($subQuery) use (
                                $user_id
                            ) {
                                $subQuery->where("buyer_id", $user_id);
                            });
                    })
                    ->whereHas("po_transaction.approver_history", function (
                        $query
                    ) {
                        $query->whereNotNull("rejected_at");
                    })
                    ->whereNotNull("approved_at");
            })
            ->when($status === "voided", function ($query) {
                $query->whereNotNull("voided_at");
            })
            ->when($status === "pr_approved", function ($query) {
                $query
                    ->where("status", "Approved")
                    ->whereNull("cancelled_at")
                    ->whereNull("voided_at")
                    ->whereHas("approver_history", function ($query) {
                        $query->whereNotNull("approved_at");
                    });
            })
            ->when($status === "pending", function ($query) use ($user_id) {
                $query
                    ->with([
                        "order" => function ($query) use ($user_id) {
                            $query
                                ->whereNotNull("buyer_id")
                                ->where("buyer_id", $user_id);
                        },
                        "po_transaction" => function ($query) use ($user_id) {
                            $query
                                ->whereNull("deleted_at")
                                ->where(function ($subQuery) {
                                    $subQuery
                                        ->where("status", "Pending")
                                        ->orWhere("status", "For Approval");
                                })
                                ->whereHas("order", function ($orderQuery) use (
                                    $user_id
                                ) {
                                    $orderQuery->where("buyer_id", $user_id);
                                });
                        },
                    ])
                    ->where(function ($query) use ($user_id) {
                        $query->where(function ($subQuery) use ($user_id) {
                            $subQuery
                                ->where("status", "Approved")
                                ->whereNotNull("approved_at")
                                ->whereHas("order", function ($orderQuery) use (
                                    $user_id
                                ) {
                                    $orderQuery
                                        ->whereNotNull("buyer_id")
                                        ->where("buyer_id", $user_id);
                                });
                        });
                    })
                    ->whereHas("po_transaction", function ($query) {
                        $query
                            ->whereNull("deleted_at")
                            ->where("status", "Pending")
                            ->orWhere("status", "For Approval");
                    });
            })
            ->when($status === "s_buyer", function ($query) {
                $query
                    ->whereHas("order", function ($orderQuery) {
                        $orderQuery
                            ->whereNotNull("buyer_id")
                            ->whereNull("supplier_id");
                    })
                    ->with([
                        "order" => function ($query) {
                            $query->whereNull("po_at");
                        },
                    ])
                    ->where("status", "Approved");
            });
    }
}
