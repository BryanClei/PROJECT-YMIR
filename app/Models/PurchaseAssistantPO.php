<?php

namespace App\Models;

use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use App\Filters\PurchaseAssistantPOFilters;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseAssistantPO extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    protected $connection = "mysql";
    protected string $default_filters = PurchaseAssistantPOFilters::class;

    protected $table = "po_transactions";

    protected $fillable = [
        "po_year_number_id",
        "pr_number",
        "po_number",
        "po_description",
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
        "total_item_price",
        "status",
        "print_status",
        "layer",
        "cap_ex",
        "description",
        "reason",
        "edit_remarks",
        "pcf_remarks",
        "ship_to_id",
        "ship_to_name",
        "approver_remarks",
        "asset",
        "sgp",
        "f1",
        "f2",
        "rush",
        "user_tagging",
        "place_order",
        "for_po_only",
        "for_po_only_id",
        "approved_at",
        "rejected_at",
        "voided_at",
        "cancelled_at",
        "updated_by",
        "approver_id",
    ];

    // public function users()
    // {
    //     return $this->belongsTo(User::class, "user_id", "id")->withTrashed();
    // }

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
        return $this->hasMany(PRItems::class, "transaction_id", "pr_number");
    }

    public function po_items()
    {
        return $this->hasMany(POItems::class, "po_id", "id")->withTrashed();
    }

    public function approver_history()
    {
        return $this->hasMany(PoHistory::class, "po_id", "id");
    }

    public function assets()
    {
        return $this->belongsTo(Assets::class, "asset", "id");
    }

    public function log_history()
    {
        return $this->hasMany(LogHistory::class, "po_id", "id");
    }

    public function pr_transaction()
    {
        return $this->belongsTo(PRTransaction::class, "pr_number", "pr_number");
    }

    public function pr_approver_history()
    {
        return $this->hasMany(PrHistory::class, "pr_id", "pr_number");
    }
}
