<?php

namespace App\Models;

use App\Filters\PoFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class POTransaction extends Model
{
    use Filterable, HasFactory, SoftDeletes;
    protected $connection = "mysql";
    protected string $default_filters = PoFilters::class;

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
        "total_item_price",
        "status",
        "layer",
        "cap_ex",
        "description",
        "reason",
        "edit_remarks",
        "pcf_remarks",
        "ship_to",
        "approver_remarks",
        "asset",
        "sgp",
        "f1",
        "f2",
        "rush",
        "user_tagging",
        "place_order",
        "approved_at",
        "rejected_at",
        "voided_at",
        "cancelled_at",
        "updated_by",
        "approver_id",
        "ep_sync",
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

    public function pr_transaction()
    {
        return $this->belongsTo(PRTransaction::class, "pr_number", "pr_number");
    }

    public function order()
    {
        return $this->hasMany(POItems::class, "po_id", "po_number");
    }

    public function pr_items()
    {
        return $this->hasMany(PRItems::class, "buyer_id", "id");
    }

    public function approver_history()
    {
        return $this->hasMany(PoHistory::class, "po_id", "id");
    }

    public function pr_approver_history()
    {
        return $this->hasMany(PrHistory::class, "pr_id", "pr_number");
    }

    public function supplier()
    {
        return $this->hasMany(Suppliers::class, "id", "supplier_id");
    }

    public function rr_transaction()
    {
        return $this->hasMany(RRTransaction::class, "po_id", "id");
    }

    public function log_history()
    {
        return $this->hasMany(LogHistory::class, "po_id", "id");
    }

    public function account_title()
    {
        return $this->belongsTo(AccountTitle::class, "account_title_id", "id");
    }

    public function company()
    {
        return $this->belongsTo(Company::class, "company_id", "id");
    }

    public function department()
    {
        return $this->belongsTo(Department::class, "department_id", "id");
    }

    public function department_unit()
    {
        return $this->belongsTo(
            DepartmentUnit::class,
            "department_unit_id",
            "id"
        );
    }

    public function sub_unit()
    {
        return $this->belongsTo(SubUnit::class, "sub_unit_id", "id");
    }

    public function location()
    {
        return $this->belongsTo(Location::class, "location_id", "id");
    }

    public function unit()
    {
        return $this->belongsTo(Units::class, "unit_id", "id");
    }

    public function business_unit()
    {
        return $this->belongsTo(BusinessUnit::class, "business_unit_id", "id");
    }
}
