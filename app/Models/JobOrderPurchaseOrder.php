<?php

namespace App\Models;

use App\Filters\JoPoApproversFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobOrderPurchaseOrder extends Model
{
    use Filterable, HasFactory, SoftDeletes;
    protected string $default_filters = JoPoApproversFilters::class;
    protected $connection = "mysql";
    protected $table = "job_order_purchase_order";
    protected $fillable = [
        "module",
        "company_id",
        "company_code",
        "company_name",
        "business_unit_id",
        "business_unit_code",
        "business_unit_name",
        "department_id",
        "department_code",
        "department_name",
        "one_charging_id",
        "one_charging_sync_id",
        "one_charging_code",
        "one_charging_name",
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, "company_id", "id");
    }
    public function business_unit()
    {
        return $this->belongsTo(BusinessUnit::class, "business_unit_id", "id");
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
    public function locations()
    {
        return $this->belongsTo(Location::class, "location_id", "id");
    }

    public function set_approver()
    {
        return $this->hasMany(
            JobOrderPurchaseOrderApprovers::class,
            "jo_purchase_order_id",
            "id"
        );
    }

    public function one_charging()
    {
        return $this->belongsTo(
            Charging::class,
            "one_charging_sync_id",
            "sync_id"
        );
    }
}
