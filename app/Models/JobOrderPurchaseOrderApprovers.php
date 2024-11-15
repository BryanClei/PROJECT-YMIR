<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOrderPurchaseOrderApprovers extends Model
{
    use HasFactory;
    protected $connection = "mysql";
    protected $table = "job_order_purchase_order_approvers";
    protected $fillable = [
        "jo_purchase_order_id",
        "approver_id",
        "approver_name",
        "base_price",
        "layer",
    ];
}
