<?php

namespace App\Models;

use App\Filters\LocationFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Location extends Model
{
    use Filterable, HasFactory, SoftDeletes;
    protected $connection = "mysql";
    protected string $default_filters = LocationFilters::class;

    protected $fillable = ["name", "code"];
    protected $hidden = ["created_at", "pivot"];

    public function sub_units()
    {
        return $this->belongsToMany(
            SubUnit::class,
            "location_sub_units",
            "location_id",
            "sub_unit_id"
        )->withTimeStamps();
    }

    // public function sub_unit_id()
    // {
    //     return $this->belongsToMany(
    //         Location::class,
    //         "location_sub_units",
    //         "location_id",
    //         "sub_unit_id"
    //     )->withTimeStamps();
    // }
}
