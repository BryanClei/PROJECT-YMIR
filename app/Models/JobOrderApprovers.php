<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOrderApprovers extends Model
{
    use HasFactory;
    protected $connection = "mysql";
    protected $fillable = [
        "job_order_id",
        "approver_id",
        "approver_name",
        "layer",
    ];
}
