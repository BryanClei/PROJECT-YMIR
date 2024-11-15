<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOrderMinMax extends Model
{
    use HasFactory;

    protected $connection = "mysql";
    protected $table = "amount_min_max";

    protected $fillable = ["amount_min", "amount_max"];
}
