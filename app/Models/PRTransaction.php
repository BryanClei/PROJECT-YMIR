<?php

namespace App\Models;

use App\Models\Assets;
use App\Filters\ExpenseFilter;
use App\Filters\PRTransactionFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PRTransaction extends Model
{
    use Filterable, HasFactory, SoftDeletes;
    protected $connection = "mysql";
    protected $table = "pr_transactions";
    protected $casts = ["remarks" => "json"];
    protected string $default_filters = PRTransactionFilters::class;

    protected $fillable = [
        "pr_number",
        "pr_year_number_id",
        "transaction_no",
        "pr_description",
        "date_needed",
        "user_id",
        "type_id",
        "type_name",
        "one_charging_id",
        "one_charging_sync_id",
        "one_charging_code",
        "one_charging_name",
        "business_unit_id",
        "business_unit_code",
        "business_unit_name",
        "company_id",
        "company_code",
        "company_name",
        "department_id",
        "department_code",
        "department_name",
        "department_unit_id",
        "department_unit_code",
        "department_unit_name",
        "location_id",
        "location_code",
        "location_name",
        "sub_unit_id",
        "sub_unit_code",
        "sub_unit_name",
        "account_title_id",
        "account_title_name",
        "supplier_id",
        "supplier_name",
        "module_name",
        "transaction_number",
        "status",
        "asset_code",
        "layer",
        "cap_ex",
        "description",
        "helpdesk_id",
        "reason",
        "edit_remarks",
        "pcf_remarks",
        "ship_to_id",
        "ship_to_name",
        "approver_remarks",
        "asset",
        "asset_code",
        "sgp",
        "f1",
        "f2",
        "rush",
        "place_order",
        "for_po_only",
        "for_po_only_id",
        "user_tagging",
        "vrid",
        "for_marketing",
        "approved_at",
        "rejected_at",
        "voided_at",
        "cancelled_at",
        "approver_id",
    ];

    public function users()
    {
        // Add module_name check before the relationship is built
        return $this->module_name === "Asset"
            ? $this->belongsTo(VladimirUser::class, "user_id", "id")
            : $this->belongsTo(User::class, "user_id", "id")->withTrashed();

        // return $this->belongsTo(User::class, "user_id", "id")->withTrashed();
    }

    // Add a specific relationship for Vladimir users
    public function vladimir_user()
    {
        return $this->belongsTo(VladimirUser::class, "user_id", "id");
    }

    // Add a specific relationship for regular users
    public function regular_user()
    {
        return $this->belongsTo(User::class, "user_id", "id")->withTrashed();
    }

    public function order()
    {
        return $this->hasMany(PRItems::class, "transaction_id", "id");
    }

    public function approver_history()
    {
        return $this->hasMany(PrHistory::class, "pr_id", "id");
    }

    public function po_transaction()
    {
        return $this->hasMany(
            POTransaction::class,
            "pr_number",
            "pr_number"
        )->withTrashed();
    }

    public function rr_transactions()
    {
        return $this->hasMany(RRTransaction::class, "pr_id", "id");
    }

    public function assets()
    {
        return $this->belongsTo(Assets::class, "asset", "id");
    }

    public function log_history()
    {
        return $this->hasMany(LogHistory::class, "pr_id", "id");
    }

    public function pr_items2()
    {
        return $this->hasMany(PRItems2::class, "transaction_id", "id");
    }

    public function vladuser()
    {
        $this->belongsTo(VladimirUser::class, "user_id", "id");
    }
}
