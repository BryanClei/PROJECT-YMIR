<?php

namespace App\Models;

use App\Filters\DepartmentUnitFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DepartmentUnit extends Model
{
    use Filterable, HasFactory, SoftDeletes;
    protected $connection = "mysql";
    protected string $default_filters = DepartmentUnitFilters::class;

    protected $fillable = ["code", "name", "department_id"];

    protected $hidden = ["created_at", "department_id"];

    protected $table = "department_units";

    public function department()
    {
        return $this->belongsTo(Department::class, "department_id", "id");
    }

    public function sub_unit()
    {
        return $this->hasMany(SubUnit::class, "department_unit_id", "id");
    }
}
