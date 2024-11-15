<?php

namespace App\Models;

use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use App\Filters\ApproverDashboardJOPOFilters;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApproverDashboardJOPO extends Model
{
    use Filterable, HasFactory, SoftDeletes;
    protected $connection = "mysql";
    protected string $default_filters = ApproverDashboardJOPOFilters::class;

    protected $table = "jo_po_transactions";

    protected $fillable = [
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
        "updated_by",
        "approver_id",
    ];

    public function users()
    {
        return $this->belongsTo(User::class, "user_id", "id")->withTrashed();
    }

    public function order()
    {
        return $this->hasMany(JobItems::class, "purchase_order_id", "id");
    }

    public function jo_po_orders()
    {
        return $this->hasMany(JoPoOrders::class, "jo_po_id", "id");
    }

    public function approver_history()
    {
        return $this->hasMany(JobHistory::class, "jo_id", "id");
    }

    public function jo_approver_history()
    {
        return $this->hasMany(JoPoHistory::class, "jo_po_id", "po_number");
    }

    public function supplier()
    {
        return $this->hasMany(Suppliers::class, "supplier_id", "id");
    }

    public function jr_transaction()
    {
        return $this->belongsTo(JobOrderTransaction::class, "jo_number", "id");
    }
}
