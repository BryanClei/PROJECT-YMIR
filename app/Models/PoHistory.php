<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoHistory extends Model
{
    use HasFactory;
    protected $connection = "mysql";
    protected $table = "po_history";
    protected $fillable = [
        "po_id",
        "approver_id",
        "approver_name",
        "approved_at",
        "rejected_at",
        "layer",
    ];
}
