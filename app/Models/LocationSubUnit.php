<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationSubUnit extends Model
{
    use HasFactory;
    protected $connection = "mysql";
    protected $table = "location_sub_units";

    protected $fillable = ["location_id", "sub_unit_id"];
}
