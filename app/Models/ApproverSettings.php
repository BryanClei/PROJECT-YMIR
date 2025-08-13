<?php

namespace App\Models;

use App\Filters\ApproverFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApproverSettings extends Model
{
    use Filterable, HasFactory, SoftDeletes;
    protected $connection = "mysql";
    protected string $default_filters = ApproverFilters::class;

    protected $fillable = [
        "module",
        "company_id",
        "company_code",
        "business_unit_id",
        "business_unit_code",
        "department_id",
        "department_code",
        "department_unit_id",
        "department_unit_code",
        "sub_unit_id",
        "sub_unit_code",
        "location_id",
        "location_code",
        "one_charging_id",
        "one_charging_sync_id",
        "one_charging_code",
        "one_charging_name",
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, "company_code", "code");
    }
    public function business_unit()
    {
        return $this->belongsTo(
            BusinessUnit::class,
            "business_unit_code",
            "code"
        );
    }
    public function department()
    {
        return $this->belongsTo(Department::class, "department_code", "code");
    }
    public function department_unit()
    {
        return $this->belongsTo(
            DepartmentUnit::class,
            "department_unit_code",
            "code"
        );
    }
    public function sub_unit()
    {
        return $this->belongsTo(SubUnit::class, "sub_unit_code", "code");
    }
    public function locations()
    {
        return $this->belongsTo(Location::class, "location_code", "code");
    }

    public function one_charging()
    {
        return $this->belongsTo(
            Charging::class,
            "one_charging_sync_id",
            "sync_id"
        );
    }

    public function set_approver()
    {
        return $this->hasMany(SetApprover::class, "approver_settings_id", "id");
    }
}
