<?php

namespace App\Models;

use App\Filters\CompanyFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use Filterable, HasFactory, SoftDeletes;
    protected $connection = "mysql";
    protected string $default_filters = CompanyFilters::class;

    protected $fillable = ["name", "code"];
    protected $hidden = ["created_at"];

    public function business_unit()
    {
        return $this->hasMany(
            BusinessUnit::class,
            "company_id",
            "id"
        )->withTrashed();
    }
}
