<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PAController;
use App\Http\Controllers\Api\PoController;
use App\Http\Controllers\Api\UomController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\TypeController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BuyerController;
use App\Http\Controllers\Api\AssetsController;
use App\Http\Controllers\Api\CanvasController;
use App\Http\Controllers\Api\ETDApiController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\SubUnitController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\FistoApiController;
use App\Http\Controllers\Api\JobOrderController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\SearchPoController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\AllowableController;
use App\Http\Controllers\Api\FinancialController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\CategoriesController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\PrApproverController;
use App\Http\Controllers\Api\SmallToolsController;
use App\Http\Controllers\Api\AccountTypeController;
use App\Http\Controllers\Api\PoApproversController;
use App\Http\Controllers\Api\AccountGroupController;
use App\Http\Controllers\Api\AccountTitleController;
use App\Http\Controllers\Api\GeneralLedgerController;
use App\Http\Controllers\Api\NormalBalanceController;
use App\Http\Controllers\Api\PRTransactionController;
use App\Http\Controllers\Api\RRTransactionController;
use App\Http\Controllers\Api\DepartmentUnitController;
use App\Http\Controllers\Api\JobOrderMinMaxController;
use App\Http\Controllers\Api\AccountSubGroupController;
use App\Http\Controllers\Api\JORRTransactionController;
use App\Http\Controllers\Api\AccountTitleUnitController;
use App\Http\Controllers\Api\ApproverSettingsController;
use App\Http\Controllers\Api\JobOrderTransactionController;
use App\Http\Controllers\Api\PoApproverDashboardController;
use App\Http\Controllers\Api\PushingErrorHandlerController;
use App\Http\Controllers\Api\JobOrderPurchaseOrderController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */
Route::group(["middleware" => ["auth_key"]], function () {
    Route::get("general_ledger_integration", [
        GeneralLedgerController::class,
        "integration_index",
    ]);
});

Route::group(["middleware" => ["auth:sanctum"]], function () {
    Route::post("logout", [UserController::class, "logout"]);
    Route::patch("reset_password/{id}", [
        UserController::class,
        "resetPassword",
    ]);
    Route::patch("change_password/{id}", [
        UserController::class,
        "changePassword",
    ]);
    Route::patch("users/archived/{id}", [UserController::class, "destroy"]);
    Route::apiResource("users", UserController::class);

    Route::patch("roles/archived/{id}", [RoleController::class, "archived"]);
    Route::apiResource("roles", RoleController::class);

    Route::patch("companies/archived/{id}", [
        CompanyController::class,
        "destroy",
    ]);

    Route::post("companies/import", [CompanyController::class, "import"]);
    Route::apiResource("companies", CompanyController::class);

    Route::patch("business-units/archived/{id}", [
        BusinessController::class,
        "destroy",
    ]);
    Route::post("business-units/import", [BusinessController::class, "import"]);
    Route::apiResource("business-units", BusinessController::class);

    Route::patch("departments/archived/{id}", [
        DepartmentController::class,
        "destroy",
    ]);
    Route::post("departments/import", [DepartmentController::class, "import"]);
    Route::apiResource("departments", DepartmentController::class);

    Route::patch("sub_units/archived/{id}", [
        SubUnitController::class,
        "destroy",
    ]);
    Route::post("sub_units/import", [SubUnitController::class, "import"]);
    Route::apiResource("sub_units", SubUnitController::class);

    Route::patch("locations/archived/{id}", [
        LocationController::class,
        "destroy",
    ]);
    Route::post("locations/import", [LocationController::class, "import"]);
    Route::apiResource("locations", LocationController::class);

    Route::patch("warehouses/archived/{id}", [
        WarehouseController::class,
        "destroy",
    ]);
    Route::apiResource("warehouses", WarehouseController::class);

    Route::patch("account_type/archived/{id}", [
        AccountTypeController::class,
        "destroy",
    ]);
    Route::post("account_type/import", [
        AccountTypeController::class,
        "import",
    ]);
    Route::apiResource("account_type", AccountTypeController::class);

    Route::patch("account_group/archived/{id}", [
        AccountGroupController::class,
        "destroy",
    ]);
    Route::post("account_group/import", [
        AccountGroupController::class,
        "import",
    ]);
    Route::apiResource("account_group", AccountGroupController::class);

    Route::patch("account_sub_group/archived/{id}", [
        AccountSubGroupController::class,
        "destroy",
    ]);
    Route::post("account_sub_group/import", [
        AccountSubGroupController::class,
        "import",
    ]);
    Route::apiResource("account_sub_group", AccountSubGroupController::class);

    Route::patch("financial_statement/archived/{id}", [
        FinancialController::class,
        "destroy",
    ]);
    Route::post("financial_statement/import", [
        FinancialController::class,
        "import",
    ]);
    Route::apiResource("financial_statement", FinancialController::class);

    Route::patch("normal_balance/archived/{id}", [
        NormalBalanceController::class,
        "destroy",
    ]);
    Route::post("normal_balance/import", [
        NormalBalanceController::class,
        "import",
    ]);
    Route::apiResource("normal_balance", NormalBalanceController::class);

    Route::patch("account_title_units/archived/{id}", [
        AccountTitleUnitController::class,
        "destroy",
    ]);
    Route::post("account_title_units/import", [
        AccountTitleUnitController::class,
        "import",
    ]);
    Route::apiResource(
        "account_title_units",
        AccountTitleUnitController::class
    );

    Route::patch("types/archived/{id}", [TypeController::class, "destroy"]);
    Route::apiResource("types", TypeController::class);

    Route::patch("uoms/archived/{id}", [UomController::class, "destroy"]);
    Route::post("uoms/import", [UomController::class, "import"]);
    Route::apiResource("uoms", UomController::class);

    Route::patch("suppliers/archived/{id}", [
        SupplierController::class,
        "destroy",
    ]);
    Route::post("suppliers/import", [SupplierController::class, "import"]);
    Route::apiResource("suppliers", SupplierController::class);

    Route::patch("units/archived/{id}", [UnitController::class, "destroy"]);
    Route::apiResource("units", UnitController::class);

    Route::patch("items/archived/{id}", [ItemController::class, "destroy"]);
    Route::post("items/import", [ItemController::class, "import"]);
    Route::apiResource("items", ItemController::class);

    Route::patch("units_department/archived/{id}", [
        DepartmentUnitController::class,
        "destroy",
    ]);
    Route::post("units_department/import", [
        DepartmentUnitController::class,
        "import",
    ]);
    Route::apiResource("units_department", DepartmentUnitController::class);

    Route::patch("account_titles/archived/{id}", [
        AccountTitleController::class,
        "destroy",
    ]);
    Route::post("account_titles/import", [
        AccountTitleController::class,
        "import",
    ]);
    Route::apiResource("account_titles", AccountTitleController::class);

    Route::get("pr_badges", [PRTransactionController::class, "pr_badge"]);
    Route::patch("return_pr_resubmit/{id}", [
        PRTransactionController::class,
        "return_resubmit",
    ]);
    Route::patch("pr_transaction/archived/{id}", [
        PRTransactionController::class,
        "destroy",
    ]);
    Route::get("download-file/{filename}", [
        PRTransactionController::class,
        "download",
    ]);
    Route::post("store_multiple/{id}", [
        PRTransactionController::class,
        "store_multiple",
    ]);
    Route::post("store_file", [PRTransactionController::class, "store_file"]);
    Route::get("assets", [PRTransactionController::class, "assets"]);
    Route::post("asset_sync", [PRTransactionController::class, "asset_sync"]);
    Route::patch("pr_transaction/resubmit/{id}", [
        PRTransactionController::class,
        "resubmit",
    ]);
    Route::patch("place_order/{id}", [BuyerController::class, "place_order"]);
    Route::get("buyer_badge", [BuyerController::class, "buyer_badge"]);
    Route::get("unit_item_price/{id}", [
        BuyerController::class,
        "item_unit_price",
    ]);
    Route::patch("po_transaction/buyer/{id}", [
        PRTransactionController::class,
        "buyer",
    ]);
    Route::apiResource("pr_transaction", PRTransactionController::class);
    Route::patch("update_remarks/{id}", [
        PoController::class,
        "update_remarks",
    ]);
    Route::get("po_badges", [PoController::class, "po_badge"]);
    Route::patch("po_transaction/resubmit/{id}", [
        PoController::class,
        "resubmit",
    ]);
    Route::get("approved_pr", [PoController::class, "approved_pr"]);
    Route::post("po_transaction/job_order_po", [
        PoController::class,
        "store_jo",
    ]);
    Route::put("po_transaction/job_order_po/resubmit/{id}", [
        PoController::class,
        "resubmit_jo",
    ]);
    Route::apiResource("po_transaction", PoController::class);

    Route::patch("approvers_settings/archived/{id}", [
        ApproverSettingsController::class,
        "destroy",
    ]);

    Route::apiResource("approvers_settings", ApproverSettingsController::class);

    Route::get("pr_approver_badge", [
        PrApproverController::class,
        "pr_approver_badge",
    ]);
    Route::patch("approved/{id}", [PrApproverController::class, "approved"]);
    Route::patch("cancelled/{id}", [PrApproverController::class, "cancelled"]);
    Route::patch("void/{id}", [PrApproverController::class, "voided"]);
    Route::patch("rejected/{id}", [PrApproverController::class, "rejected"]);

    Route::patch("approved_jo/{id}", [
        PrApproverController::class,
        "approved_jo",
    ]);
    Route::patch("cancelled_jo/{id}", [
        PrApproverController::class,
        "cancelled_jo",
    ]);
    Route::patch("void_jo/{id}", [PrApproverController::class, "void_jo"]);
    Route::patch("rejected_jo/{id}", [
        PrApproverController::class,
        "rejected_jo",
    ]);

    Route::get("job_approver", [PrApproverController::class, "job_order"]);
    Route::get("expense_approver", [PrApproverController::class, "expense"]);
    Route::get("assets_approver", [
        PrApproverController::class,
        "assets_approver",
    ]);

    Route::apiResource("approver_dashboard", PrApproverController::class);

    Route::patch("job_order/archived/{id}", [
        JobOrderController::class,
        "destroy",
    ]);

    Route::apiResource("job_order", JobOrderController::class);

    Route::patch("po_approver/archived/{id}", [
        PoApproversController::class,
        "destroy",
    ]);

    Route::apiResource("po_approver", PoApproversController::class);

    Route::apiResource("canvas_approver", CanvasController::class);

    Route::patch("expense/archived/{id}", [
        ExpenseController::class,
        "destroy",
    ]);
    Route::patch("expense/resubmit/{id}", [
        ExpenseController::class,
        "resubmit",
    ]);
    Route::apiResource("expense", ExpenseController::class);

    Route::patch("cancel_jo/{id}", [
        JobOrderTransactionController::class,
        "cancel_jo",
    ]);

    Route::patch("void_jo/{id}", [
        JobOrderTransactionController::class,
        "voided_jo",
    ]);

    Route::get("pa_jo_badge", [
        JobOrderTransactionController::class,
        "pa_jo_badge",
    ]);
    Route::patch("jo_order_transaction/resubmit/{id}", [
        JobOrderTransactionController::class,
        "resubmit",
    ]);
    Route::apiResource(
        "job_order_transaction",
        JobOrderTransactionController::class
    );

    Route::get("po_approver_dashboard/view/{id}", [
        PoApproverDashboardController::class,
        "view",
    ]);

    Route::get("po_approver_dashboard/view_jo_po", [
        PoApproverDashboardController::class,
        "approver_index_jo_po",
    ]);

    Route::get("po_approver_dashboard/view_jo_po/{id}", [
        PoApproverDashboardController::class,
        "approver_view_jo_po",
    ]);

    Route::patch("po_approver_dashboard/approve_po/{id}", [
        PoApproverDashboardController::class,
        "approved",
    ]);
    Route::patch("po_approver_dashboard/approve_jo_po/{id}", [
        PoApproverDashboardController::class,
        "approved_jo_po",
    ]);
    Route::patch("po_approver_dashboard/reject_po/{id}", [
        PoApproverDashboardController::class,
        "rejected",
    ]);
    Route::patch("po_approver_dashboard/rejected_jo_po/{id}", [
        PoApproverDashboardController::class,
        "rejected_jo_po",
    ]);
    Route::patch("po_approver_dashboard/cancel_po/{id}", [
        PoApproverDashboardController::class,
        "cancel",
    ]);
    Route::apiResource(
        "po_approver_dashboard",
        PoApproverDashboardController::class
    );
    Route::patch("categories/archived/{id}", [
        CategoriesController::class,
        "destroy",
    ]);

    Route::post("categories/import", [CategoriesController::class, "import"]);

    Route::apiResource("categories", CategoriesController::class);

    Route::post("asset/import", [AssetsController::class, "import"]);
    Route::patch("asset/archived/{id}", [AssetsController::class, "destroy"]);
    Route::apiResource("asset", AssetsController::class);

    Route::patch("resubmit_pr_asset/{id}", [
        PAController::class,
        "resubmit_pr_asset",
    ]);
    Route::patch("edit_unit_price/{id}", [
        PAController::class,
        "edit_unit_price",
    ]);
    Route::patch("return_pr/{id}", [PAController::class, "return_pr"]);
    Route::patch("return_jo_po/{id}", [PAController::class, "return_jo_po"]);
    Route::get("pa_badge", [PAController::class, "tagging_badge"]);
    Route::patch("update_jo_po/{id}", [PAController::class, "update_jo"]);
    Route::patch("update_jo_price/{id}", [PAController::class, "update_price"]);
    Route::get("purchase_assistant/view/{id}", [PAController::class, "view"]);
    Route::get("purchase_assistant/view_po/{id}", [
        PAController::class,
        "viewpo",
    ]);
    Route::get("purchase_assistant/view_jo", [PAController::class, "index_jo"]);
    Route::get("purchase_assistant/view_jo/{id}", [
        PAController::class,
        "view_jo",
    ]);
    Route::get("purchase_assistant/view_jo_po", [
        PAController::class,
        "index_jo_po",
    ]);
    Route::get("purchase_assistant/purchase_order", [
        PAController::class,
        "index_purchase_order",
    ]);
    Route::patch("purchase_assistant/update_buyer/{id}", [
        PAController::class,
        "update_buyer",
    ]);
    Route::apiResource("purchase_assistant", PAController::class);
    Route::patch("return_po", [BuyerController::class, "return_po"]);
    Route::patch("buyer/update_price", [
        BuyerController::class,
        "update_price",
    ]);
    Route::get("buyer/view/{id}", [BuyerController::class, "view"]);
    Route::get("buyer/view_to_po/{id}", [BuyerController::class, "viewto_po"]);
    Route::patch("cancel_po/{id}", [PoController::class, "cancel_po"]);
    Route::patch("cancel_jo_po/{id}", [PoController::class, "cancel_jo_po"]);
    Route::get("buyer/view_po/{id}", [PoController::class, "view"]);
    Route::patch("return_pr_items", [
        BuyerController::class,
        "return_pr_items",
    ]);
    Route::get("buyer/po", [BuyerController::class, "index_po"]);
    Route::get("buyer/rr", [BuyerController::class, "index_rr"]);
    Route::apiResource("buyer", BuyerController::class);

    Route::get("rr_badge", [RRTransactionController::class, "rr_badge"]);
    // Route::get("rr_asset", [RRTransactionController::class, "index_asset"]);
    Route::patch("cancel_rr/{id}", [
        RRTransactionController::class,
        "cancel_rr",
    ]);
    Route::get("log_history", [RRTransactionController::class, "logs"]);
    Route::get("reports_pr", [RRTransactionController::class, "report_pr"]);
    Route::get("reports_po", [RRTransactionController::class, "report_po"]);
    Route::get("reports_rr", [RRTransactionController::class, "report_rr"]);
    Route::get("approved_po/po_to_rr_display", [
        RRTransactionController::class,
        "index_po_approved",
    ]);
    Route::get("asset_vladimir/{id}", [
        RRTransactionController::class,
        "asset_vladimir",
    ]);
    Route::put("asset_vladimir/sync", [
        RRTransactionController::class,
        "asset_sync",
    ]);
    Route::get("approved_po/po_to_rr_display/{id}", [
        RRTransactionController::class,
        "view_po_approved",
    ]);
    Route::apiResource("rr_transaction", RRTransactionController::class);

    Route::get("reports_jo", [JORRTransactionController::class, "report_jo"]);

    Route::get("reports_jo_po", [
        JORRTransactionController::class,
        "report_jo_po",
    ]);

    Route::patch("reason/{id}", [JORRTransactionController::class, "reason"]);

    Route::patch("cancel_jo_rr/{id}", [
        JORRTransactionController::class,
        "cancel_jo_rr",
    ]);

    Route::get("approved_pos_for_job_order_report_display", [
        JORRTransactionController::class,
        "view_approve_jo_po",
    ]);

    Route::get("approved_pos_for_job_order_report_display/{id}", [
        JORRTransactionController::class,
        "view_single_approve_jo_po",
    ]);

    Route::apiResource(
        "job_order_report_transaction",
        JORRTransactionController::class
    );

    Route::apiResource("allowable_percentage", AllowableController::class);

    Route::apiResource("etd_api", ETDApiController::class);

    Route::apiResource("fisto_api", FistoApiController::class);

    Route::apiResource("error_handler", PushingErrorHandlerController::class);

    Route::apiResource("search_po", SearchPoController::class);

    Route::apiResource("general_ledger", GeneralLedgerController::class);

    Route::patch("small_tools/archived/{id}", [
        SmallToolsController::class,
        "destroy",
    ]);
    Route::post("small_tools/import", [SmallToolsController::class, "import"]);
    Route::apiResource("small_tools", SmallToolsController::class);

    Route::patch("job_order_purchase_order/archived/{id}", [
        JobOrderPurchaseOrderController::class,
        "destroy",
    ]);

    Route::apiResource(
        "job_order_purchase_order",
        JobOrderPurchaseOrderController::class
    );

    Route::apiResource("job_order_min_max", JobOrderMinMaxController::class);
});
Route::post("login", [UserController::class, "login"]);
