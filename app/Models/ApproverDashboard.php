<?php

namespace App\Models;

use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use App\Filters\ApproverDashboardFilters;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApproverDashboard extends Model
{
    use Filterable, HasFactory, SoftDeletes;
    protected $connection = "mysql";
    protected string $default_filters = ApproverDashboardFilters::class;

    protected $table = "po_transactions";

    protected $fillable = [
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
        return $this->belongsTo(User::class, "user_id", "id")->withTrashed();
    }

    public function order()
    {
        return $this->hasMany(POItems::class, "po_id", "id");
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
        return $this->hasMany(Suppliers::class, "supplier_id", "id");
    }

    public function log_history()
    {
        return $this->hasMany(LogHistory::class, "po_id", "id");
    }

    public function rr_transactions()
    {
        return $this->hasMany(RRTransaction::class, "po_id", "id");
    }
}
