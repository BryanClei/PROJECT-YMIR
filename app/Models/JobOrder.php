<?php

namespace App\Models;

use App\Models\Assets;
use App\Filters\JobOrderFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobOrder extends Model
{
    use Filterable, HasFactory, SoftDeletes;
    protected $connection = "mysql";
    protected string $default_filters = JobOrderFilters::class;

    protected $table = "job_order";
    protected $fillable = [
        "module",
        "company_id",
        "business_unit_id",
        "department_id",
        "department_unit_id",
        "sub_unit_id",
        "location_id",
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
        return $this->hasMany(JobOrderApprovers::class, "job_order_id", "id");
    }
}
