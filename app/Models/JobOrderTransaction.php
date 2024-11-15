<?php

namespace App\Models;

use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use App\Filters\JobOrderTransactionFilters;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobOrderTransaction extends Model
{
    use Filterable, SoftDeletes, HasFactory;
    protected $connection = "mysql";
    protected string $default_filters = JobOrderTransactionFilters::class;

    protected $table = "jo_transactions";

    protected $fillable = [
        "jo_year_number_id",
        "jo_number",
        "jo_description",
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
        "asset",
        "module_name",
        "total_price",
        "status",
        "layer",
        "description",
        "reason",
        "approved_at",
        "rejected_at",
        "voided_at",
        "cancelled_at",
        "approver_id",
        "rush",
        "outside_labor",
        "cap_ex",
        "helpdesk_id",
    ];

    public function users()
    {
        return $this->belongsTo(User::class, "user_id", "id")->withTrashed();
    }

    public function order()
    {
        return $this->hasMany(JobItems::class, "jo_transaction_id", "id");
    }

    public function jo_po_transaction()
    {
        return $this->hasMany(JOPOTransaction::class, "jo_number", "jo_number");
    }

    public function approver_history()
    {
        return $this->hasMany(JobHistory::class, "jo_id", "id");
    }

    public function jo_approver_history()
    {
        return $this->hasMany(JobHistory::class, "jo_id", "id");
    }

    public function log_history()
    {
        return $this->hasMany(LogHistory::class, "jo_id", "id");
    }
}
