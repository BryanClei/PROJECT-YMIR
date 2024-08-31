<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JoPoOrders extends Model
{
    use HasFactory, SoftDeletes;
    protected $connection = "mysql";
    protected $table = "jo_po_orders";
    protected $fillable = [
        "jo_transaction_id",
        "jo_item_id",
        "jo_po_id",
        "description",
        "uom_id",
        "quantity",
        "quantity_serve",
        "unit_price",
        "total_price",
        "remarks",
        "attachment",
        "asset",
    ];
    protected $hidden = ["created_at"];

    protected $casts = ["attachment" => "array"];

    public function transaction()
    {
        return $this->belongsTo(
            JobOrderTransaction::class,
            "jo_transaction_id",
            "id"
        );
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class, "uom_id", "id");
    }

    public function asset()
    {
        return $this->belongsTo(Assets::class, "asset", "id");
    }
}
