<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrHistory extends Model
{
    use HasFactory;
    protected $connection = "mysql";
    protected $table = "pr_approvers_history";
    protected $fillable = [
        "pr_id",
        "approver_id",
        "approver_name",
        "approved_at",
        "rejected_at",
        "layer",
    ];
}
