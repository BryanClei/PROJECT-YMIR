<?php

namespace App\Models;

use App\Filters\BuyerJOPOFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BuyerJobOrderPO extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    protected $connection = "mysql";
    protected string $default_filters = BuyerJOPOFilters::class;

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
        "total_item_price",
        "supplier_id",
        "supplier_name",
        "status",
        "layer",
        "description",
        "reason",
        "edit_remarks",
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
        "ship_to_id",
        "ship_to_name",
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
}
