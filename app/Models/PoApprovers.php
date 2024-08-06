<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PoApprovers extends Model
{
    use HasFactory;
    protected $connection = "mysql";
    protected $fillable = [
        "po_settings_id",
        "approver_id",
        "approver_name",
        "price_range",
        "layer",
    ];
}
