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
        "edit_remarks",
        "asset",
        "sgp",
        "f1",
        "f2",
        "rush",
        "place_order",
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
        return $this->belongsTo(PRTransaction::class, "pr_number", "id");
    }

    public function pr_approver_history()
    {
        return $this->hasMany(PrHistory::class, "id", "pr_number");
    }
}
