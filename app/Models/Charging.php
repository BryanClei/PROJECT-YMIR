<?php

namespace App\Models;

use App\Filters\OneChargingFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Charging extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected string $default_filters = OneChargingFilters::class;

    protected $connection = "mysql";

    protected $table = "one_charging";

    protected $fillable = [
        "code",
        "name",
        "company_id",
        "company_code",
        "company_name",
        "business_unit_id",
        "business_unit_code",
        "business_unit_name",
        "department_id",
        "department_code",
        "department_name",
        "department_unit_id",
        "department_unit_code",
        "department_unit_name",
        "sub_unit_id",
        "sub_unit_code",
        "sub_unit_name",
        "location_id",
        "location_code",
        "location_name",
        "deleted_at",
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

    public function location()
    {
        return $this->belongsTo(Location::class, "location_code", "code");
    }
}
