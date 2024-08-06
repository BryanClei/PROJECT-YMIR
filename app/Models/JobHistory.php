<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobHistory extends Model
{
    use HasFactory;
    protected $connection = "mysql";
    protected $table = "jo_history";
    protected $fillable = [
        "jo_id",
        "approver_id",
        "approver_name",
        "approved_at",
        "rejected_at",
        "layer",
    ];
}
