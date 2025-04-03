<?php

namespace App\Filters;

use App\Models\User;
use App\Models\JoPoHistory;
use Essa\APIToolKit\Filters\QueryFilters;

class JoPoFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "po_year_number_id",
        "jo_number",
        "po_number",
        "po_description",
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
        "module_name",
        "status",
        "layer",
        "description",
        "reason",
        "asset",
        "sgp",
        "f1",
        "f2",
    ];

    protected array $relationSearch = [
        "jo_transaction" => ["jo_year_number_id"],
    ];

    protected function processSearch($search)
    {
        // Join the required relationships first
        foreach ($this->relationSearch as $relation => $columns) {
            $this->builder->leftJoin(
                $relation,
                "jo_transaction.jo_number",
                "=",
                $relation . ".jo_number"
            );
        }

        $this->builder->where(function ($query) use ($search) {
            // Search in main table columns
            foreach ($this->columnSearch as $column) {
                $query->orWhere(
                    "jo_transaction." . $column,
                    "like",
                    "%{$search}%"
                );
            }

            // Search in relationship columns
            foreach ($this->relationSearch as $table => $columns) {
                foreach ($columns as $column) {
                    $query->orWhere(
                        $table . "." . $column,
                        "like",
                        "%{$search}%"
                    );
                }
            }
        });
    }

    public function status($status)
    {
        $user_uid = Auth()->user()->id;

        $user_id = User::where("id", $user_uid)
            ->get()
            ->first();

        $po_id = JoPoHistory::where("approver_id", $user_uid)
            ->get()
            ->pluck("jo_po_id");
        $layer = JoPoHistory::where("approver_id", $user_uid)
            ->get()
            ->pluck("layer");
        $approver_histories = JoPoHistory::where(
            "approver_id",
            $user_uid
        )->get();

        $this->builder
            ->when($status === "approved", function ($query) {
                $query
                    ->where(function ($query) {
                        $query
                            ->where("status", "For Receiving")
                            ->orWhere("status", "Approved");
                    })
                    ->whereNull("cancelled_at")
                    ->whereNull("rejected_at")
                    ->whereNull("voided_at")
                    ->whereHas("jo_approver_history", function ($query) {
                        $query->whereNotNull("approved_at");
                    });
            })
            ->when($status === "pending", function ($query) {
                $query
                    ->where(function ($query) {
                        $query
                            ->where("status", "For Approval")
                            ->orWhere("status", "Pending");
                    })
                    ->whereNull("cancelled_at")
                    ->whereNull("rejected_at")
                    ->whereNull("voided_at");
            })
            ->when($status === "cancelled", function ($query) {
                $query
                    ->where("status", "Cancelled")
                    ->whereNotNull("cancelled_at")
                    ->whereNull("direct_po")
                    ->whereNull("approved_at")
                    ->withTrashed();
            })
            ->when($status === "voided", function ($query) {
                $query->where("status", "Voided");
            })
            ->when($status === "rejected", function ($query) {
                $query->where("status", "Reject")->whereNotNull("rejected_at");
            })
            ->when($status === "for_receiving", function ($query) {
                $query
                    ->with([
                        "jo_po_orders" => function ($query) {
                            $query->whereColumn(
                                "quantity",
                                "<>",
                                "quantity_serve"
                            );
                        },
                    ])
                    ->where("status", "For Receiving")
                    ->whereNull("cancelled_at")
                    ->whereNull("rejected_at")
                    ->whereNull("voided_at")
                    ->whereHas("jo_approver_history", function ($query) {
                        $query->whereNotNull("approved_at");
                    })
                    ->whereHas("jo_po_orders", function ($query) {
                        $query->whereColumn("quantity", ">", "quantity_serve");
                    });
            })
            ->when($status === "for_receiving_user", function ($query) use (
                $user_uid
            ) {
                $query
                    ->whereHas("jo_transaction", function ($query) use (
                        $user_uid
                    ) {
                        $query->where("user_id", $user_uid);
                    })
                    ->with([
                        "jo_po_orders" => function ($query) {
                            $query->whereColumn(
                                "quantity",
                                ">",
                                "quantity_serve"
                            );
                        },
                    ])
                    ->where("status", "For Receiving")
                    ->whereNull("cancelled_at")
                    ->whereNull("rejected_at")
                    ->whereNull("voided_at")
                    ->whereHas("jo_approver_history", function ($query) {
                        $query->whereNotNull("approved_at");
                    })
                    ->whereHas("jo_po_orders", function ($query) {
                        $query->whereColumn("quantity", "<>", "quantity_serve");
                    });
            })
            ->when($status === "return_po", function ($query) {
                $query->where("status", "Return")->whereNull("rejected_at");
            });
    }
}
