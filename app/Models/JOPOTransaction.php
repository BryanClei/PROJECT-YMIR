<?php

namespace App\Models;

use App\Models\Suppliers;
use App\Filters\JoPoFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JOPOTransaction extends Model
{
    use Filterable, HasFactory, SoftDeletes;
    protected $connection = "mysql";
    protected string $default_filters = JoPoFilters::class;

    protected $table = "jo_po_transactions";

    protected $fillable = [
        "po_year_number_id",
        "jo_number",
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
        "module_name",
        "total_item_price",
        "pcf_remarks",
        "ship_to_id",
        "ship_to_name",
        "supplier_id",
        "supplier_name",
        "status",
        "print_status",
        "layer",
        "description",
        "reason",
        "edit_remarks",
        "approver_remarks",
        "asset",
        "sgp",
        "f1",
        "f2",
        "rush",
        "outside_labor",
        "direct_po",
        "cap_ex",
        "helpdesk_id",
        "cip_number",
        "for_po_only",
        "for_po_only_id",
        "approved_at",
        "rejected_at",
        "voided_at",
        "cancelled_at",
        "updated_by",
        "approver_id",
    ];

    public function users()
    {
        return $this->belongsTo(User::class, "user_id", "id")->withTrashed();
    }

    public function jo_transaction()
    {
        return $this->belongsTo(
            JobOrderTransactionPA::class,
            "jo_number",
            "jo_number"
        )->withTrashed();
    }

    public function order()
    {
        return $this->hasMany(
            JobItems::class,
            "purchase_order_id",
            "id"
        )->withTrashed();
    }

    public function jo_po_orders()
    {
        return $this->hasMany(
            JoPoOrders::class,
            "jo_po_id",
            "id"
        )->withTrashed();
    }

    public function approver_history()
    {
        return $this->hasMany(JobHistory::class, "jo_id", "id");
    }

    public function jo_approver_history()
    {
        return $this->hasMany(JoPoHistory::class, "jo_po_id", "id");
    }

    public function jo_rr_transaction()
    {
        return $this->hasMany(JORRTransaction::class, "jo_po_id", "id");
    }

    public function supplier()
    {
        return $this->belongsTo(Suppliers::class, "supplier_id", "id");
    }

    public function log_history()
    {
        return $this->hasMany(LogHistory::class, "jo_po_id", "id");
    }

    public function account_title()
    {
        return $this->belongsTo(AccountTitle::class, "account_title_id", "id");
    }

    public function business_unit()
    {
        return $this->belongsTo(BusinessUnit::class, "business_unit_id", "id");
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
}
