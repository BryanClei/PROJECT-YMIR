<?php

namespace App\Models;

use App\Filters\BusinessUnitFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessUnit extends Model
{
    use Filterable, HasFactory, SoftDeletes;
    protected $connection = "mysql";
    protected string $default_filters = BusinessUnitFilters::class;

    protected $fillable = ["name", "code", "company_id"];
    protected $hidden = ["created_at"];

    public function company()
    {
        return $this->belongsTo(
            Company::class,
            "company_id",
            "id"
        )->withTrashed();
    }
    public function department()
    {
        return $this->hasMany(
            Department::class,
            "business_unit_id",
            "id"
        )->withTrashed();
    }
}
