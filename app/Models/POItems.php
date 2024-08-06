<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class POItems extends Model
{
    use HasFactory, SoftDeletes;
    protected $connection = "mysql";
    protected $table = "po_orders";
    protected $fillable = [
        "po_id",
        "pr_id",
        "pr_item_id",
        "item_id",
        "item_code",
        "item_name",
        "supplier_id",
        "uom_id",
        "price",
        "quantity",
        "quantity_serve",
        "total_price",
        "attachment",
        "buyer_id",
        "buyer_name",
        "remarks",
        "warehouse_id",
    ];

    public function supplier()
    {
        return $this->belongsTo(Suppliers::class, "supplier_id", "id");
    }
}
