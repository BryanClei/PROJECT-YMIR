<?php

namespace App\Models;

use App\Models\POItems;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PRItems extends Model
{
    use HasFactory, SoftDeletes;
    protected $connection = "mysql";
    protected $table = "pr_items";
    protected $fillable = [
        "transaction_id",
        "item_id",
        "item_code",
        "item_name",
        "uom_id",
        "po_at",
        "purchase_order_id",
        "buyer_id",
        "buyer_name",
        "quantity",
        "remarks",
        "attachment",
        "assets",
        "warehouse_id",
        "category_id",
    ];
    protected $hidden = ["created_at"];

    protected $casts = [
        "attachment" => "json",
    ];

    public function transaction()
    {
        return $this->belongsTo(PRTransaction::class, "transaction_id", "id");
    }

    public function po_transaction()
    {
        return $this->belongsTo(
            POTransaction::class,
            "id",
            "purchase_order_id"
        );
    }

    public function item()
    {
        return $this->belongsTo(Items::class, "item_id", "id");
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class, "uom_id", "id");
    }

    public function asset()
    {
        return $this->belongsTo(Assets::class, "assets", "id");
    }

    public function po_order()
    {
        return $this->hasMany(POItems::class, "po_id", "id");
    }

    public function category()
    {
        return $this->belongsTo(Categories::class, "category_id", "id");
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouses::class, "warehouse_id", "id");
    }
}
