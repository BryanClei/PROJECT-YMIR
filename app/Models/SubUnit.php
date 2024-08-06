<?php

namespace App\Models;

use App\Filters\SubUnitFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubUnit extends Model
{
    use Filterable, HasFactory, SoftDeletes;
    protected $connection = "mysql";
    protected string $default_filters = SubUnitFilters::class;

    protected $fillable = ["name", "code", "department_unit_id"];
    protected $hidden = ["created_at", "department_unit_id", "pivot"];

    public function locations()
    {
        return $this->belongsToMany(
            Location::class,
            "location_sub_units",
            "sub_unit_id",
            "location_id"
        )->withTimeStamps();
    }

    public function department_unit()
    {
        return $this->belongsTo(
            DepartmentUnit::class,
            "department_unit_id",
            "id"
        )->withTrashed();
    }
}
