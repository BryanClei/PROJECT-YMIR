<?php

namespace App\Models;

use App\Filters\DepartmentFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use Filterable, HasFactory, SoftDeletes;
    protected $connection = "mysql";
    protected string $default_filters = DepartmentFilters::class;

    protected $fillable = ["name", "code", "business_unit_id"];
    protected $hidden = ["created_at", "business_unit_id"];

    public function business_unit()
    {
        return $this->belongsTo(
            BusinessUnit::class,
            "business_unit_id",
            "id"
        )->withTrashed();
    }
    public function department_unit()
    {
        return $this->HasMany(DepartmentUnit::class, "department_id", "id");
    }
}
