<?php

namespace App\Models;

use App\Filters\PoApproversFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class POSettings extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    protected $connection = "mysql";
    protected string $default_filters = PoApproversFilters::class;

    protected $table = "po_settings";

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

    public function set_approver()
    {
        return $this->hasMany(PoApprovers::class, "po_settings_id", "id");
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
