<?php

namespace App\Models;

use App\Models\POTransaction;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use App\Filters\PurchaseAssistantFilters;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseAssistant extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    protected $connection = "mysql";
    protected string $default_filters = PurchaseAssistantFilters::class;

    protected $table = "pr_transactions";

    protected $fillable = [
        "pr_number",
        "pr_year_number_id",
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
        "pcf_remarks",
        "ship_to_id",
        "ship_to_name",
        "supplier_id",
        "supplier_name",
        "module_name",
        "transaction_number",
        "status",
        "layer",
        "description",
        "reason",
        "asset",
        "sgp",
        "f1",
        "f2",
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

    public function po_transaction()
    {
        return $this->hasMany(
            POTransaction::class,
            "pr_number",
            "pr_number"
        )->withTrashed();
    }

    public function approver_history()
    {
        return $this->hasMany(PrHistory::class, "pr_id", "id");
    }

    public function assets()
    {
        return $this->belongsTo(Assets::class, "asset", "id");
    }

    public function log_history()
    {
        return $this->hasMany(LogHistory::class, "pr_id", "id");
    }
}
