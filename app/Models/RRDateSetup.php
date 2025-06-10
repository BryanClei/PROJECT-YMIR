<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RRDateSetup extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "rr_date_setup";

    protected $fillable = [
        "setup_name",
        "previous_days",
        "forward_days",
        "previous_month",
        "threshold_days",
    ];
}
