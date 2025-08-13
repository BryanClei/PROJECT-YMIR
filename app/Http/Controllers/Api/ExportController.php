<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;

class ExportController extends Controller
{
    public function export_logs()
    {
        $filename = "log_history.csv";

        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
        ];

        $callback = function () {
            $file = fopen("php://output", "w");

            // Header row
            fputcsv($file, [
                "Activity",
                "PR Number",
                "PO Number",
                "JO Number",
                "JOPO Number",
            ]);

            // Chunk the data
            DB::table("log_history")
                ->leftJoin(
                    "pr_transactions",
                    "log_history.pr_id",
                    "=",
                    "pr_transactions.id"
                )
                ->leftJoin(
                    "po_transactions",
                    "log_history.po_id",
                    "=",
                    "po_transactions.id"
                )
                ->leftJoin(
                    "jo_transactions",
                    "log_history.jo_id",
                    "=",
                    "jo_transactions.id"
                )
                ->leftJoin(
                    "jo_po_transactions",
                    "log_history.jo_po_id",
                    "=",
                    "jo_po_transactions.id"
                )
                ->select(
                    "log_history.activity",
                    "pr_transactions.pr_year_number_id as pr_number",
                    "po_transactions.po_year_number_id as po_number",
                    "jo_transactions.jo_year_number_id as jo_number",
                    "jo_po_transactions.po_year_number_id as jo_po_number"
                )
                ->orderBy("log_history.id")
                ->chunk(3000, function ($logs) use ($file) {
                    foreach ($logs as $log) {
                        fputcsv($file, [
                            $log->activity ?? "N/A",
                            $log->pr_number ?? "N/A",
                            $log->po_number ?? "N/A",
                            $log->jo_number ?? "N/A",
                            $log->jo_po_number ?? "N/A",
                        ]);
                    }
                });

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function export_pr_summary()
    {
        $filename = "pr_summary.csv";
        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
        ];

        $callback = function () {
            $file = fopen("php://output", "w");

            // Header row
            fputcsv($file, [
                "PR Number",
                "Item Code",
                "Item Description",
                "Type",
                "Qty Ordered",
                "Unit Price",
                "UOM",
                "PR Date",
                "PR Approved Date",
                "PR Approved 1",
                "PR Approved Date 1",
                "PR Approved 2",
                "PR Approved Date 2",
                "Date Needed",
                "Requisitioner",
                "Department",
                "Location Code",
                "Location",
                "Account Title",
                "PR Authorization Status",
                "Qty Delivered",
                "Note to Buyer",
                "PO Number",
                "Supplier",
                "Date Received/Delivered",
                "Reference Number",
                "RR Number",
                "Receipt Date(DR/SI)",
                "RR Qty Received",
                "Assigned Buyer",
                "PO Date",
                "PO Authorization Status",
            ]);

            // Chunk the data
            DB::table("pr_items")
                ->leftJoin(
                    "pr_transactions",
                    "pr_items.transaction_id",
                    "=",
                    "pr_transactions.id"
                )
                ->leftJoin("uoms", "pr_items.uom_id", "=", "uoms.id")
                ->leftJoin(
                    DB::raw("(
                    SELECT 
                        pr_id,
                        MAX(CASE WHEN row_num = 1 THEN approved_at END) as last_approved_date,
                        MAX(CASE WHEN row_num = 1 THEN approver_name END) as last_approver,
                        MAX(CASE WHEN row_num = 2 THEN approved_at END) as second_last_approved_date,
                        MAX(CASE WHEN row_num = 2 THEN approver_name END) as second_last_approver
                    FROM (
                        SELECT 
                            pr_id,
                            approved_at,
                            approver_name,
                            ROW_NUMBER() OVER (PARTITION BY pr_id ORDER BY layer DESC) as row_num
                        FROM pr_approvers_history
                        WHERE approved_at IS NOT NULL
                    ) ranked_approvers
                    WHERE row_num <= 2
                    GROUP BY pr_id
                ) as last_two_approvers"),
                    "pr_transactions.id",
                    "=",
                    "last_two_approvers.pr_id"
                )
                ->leftJoin(
                    "one_charging",
                    "pr_transactions.one_charging_sync_id",
                    "=",
                    "one_charging.sync_id"
                )
                ->select(
                    "pr_transactions.pr_year_number_id as pr_year_id",
                    "pr_items.item_code as pr_item_code",
                    "pr_items.item_name as pr_item_name",
                    "pr_transactions.module_name as pr_module",
                    "pr_items.quantity as pr_item_quantity",
                    "pr_items.unit_price as pr_item_unit_price",
                    "uoms.name as uom_name",
                    "pr_transactions.created_at as pr_date",
                    "pr_transactions.approved_at as pr_approved_date",
                    "last_two_approvers.last_approved_date as pr_approved_date_1",
                    "last_two_approvers.last_approver as pr_approved_1",
                    "last_two_approvers.second_last_approved_date as pr_approved_date_2",
                    "last_two_approvers.second_last_approver as pr_approved_2",
                    "pr_transactions.date_needed as pr_date_needed",
                    "pr_transactions.user_id as pr_user_id",
                    "pr_transactions.department_name as pr_department",
                    "pr_transactions.location_code as pr_location_code",
                    "pr_transactions.location_name as pr_location_name",
                    "pr_transactions.account_title_name as pr_account_title",
                    "pr_transactions.status as pr_status"
                )
                ->orderBy("pr_items.id")
                ->chunk(3000, function ($pr_items) use ($file) {
                    foreach ($pr_items as $pr_item) {
                        // Determine which database to use for user lookup
                        $userConnection =
                            $pr_item->pr_module === "asset"
                                ? "vladimirDB"
                                : "mysql";

                        // Get user information from the appropriate database
                        $user = null;
                        if ($pr_item->pr_user_id) {
                            try {
                                $user = DB::connection($userConnection)
                                    ->table("users")
                                    ->select(
                                        DB::raw(
                                            "CONCAT(first_name, ' ', last_name) as full_name"
                                        )
                                    )
                                    ->where("id", $pr_item->pr_user_id)
                                    ->first();
                            } catch (\Exception $e) {
                                \Log::error(
                                    "Error fetching user from {$userConnection}: " .
                                        $e->getMessage()
                                );
                            }
                        }

                        $userName = $user ? $user->full_name : "N/A";

                        fputcsv($file, [
                            $pr_item->pr_year_id ?? "N/A",
                            $pr_item->pr_item_code ?? "N/A",
                            $pr_item->pr_item_name ?? "N/A",
                            $pr_item->pr_module ?? "N/A",
                            $pr_item->pr_item_quantity ?? "N/A",
                            $pr_item->pr_item_unit_price ?? "0",
                            $pr_item->uom_name ?? "N/A",
                            $pr_item->pr_date ?? "N/A",
                            $pr_item->pr_approved_date ?? "N/A",
                            $pr_item->pr_approved_1 ?? "N/A",
                            $pr_item->pr_approved_date_1 ?? "N/A",
                            $pr_item->pr_approved_2 ?? "N/A",
                            $pr_item->pr_approved_date_2 ?? "N/A",
                            $pr_item->pr_date_needed ?? "N/A",
                            $userName,
                            // Add placeholders for remaining columns
                            $pr_item->pr_department ?? "N/A",
                            $pr_item->pr_location_code ?? "N/A",
                            $pr_item->pr_location_name ?? "N/A",
                            $pr_item->pr_account_title ?? "N/A",
                            $pr_item->pr_status ?? "N/A",
                            "N/A", // Qty Delivered
                            "N/A", // Note to Buyer
                            "N/A", // PO Number
                            "N/A", // Supplier
                            "N/A", // Date Received/Delivered
                            "N/A", // Reference Number
                            "N/A", // RR Number
                            "N/A", // Receipt Date(DR/SI)
                            "N/A", // RR Qty Received
                            "N/A", // Assigned Buyer
                            "N/A", // PO Date
                            "N/A", // PO Authorization Status
                        ]);
                    }
                });

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
