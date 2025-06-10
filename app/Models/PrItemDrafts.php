<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PrItemDrafts extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "pr_draft_items";

    protected $fillable = [
        "pr_draft_id",
        "item_id",
        "item_code",
        "item_name",
        "category_id",
        "uom_id",
        "item_stock",
        "unit_price",
        "total_price",
        "quantity",
        "remarks",
        "assets",
        "warehouse_id",
    ];

    public function pr_draft()
    {
        return $this->belongsTo(PrDrafts::class, "pr_draft_id", "pr_draft_id");
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

    public function category()
    {
        return $this->belongsTo(Categories::class, "category_id", "id");
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, "warehouse_id", "id");
    }
}
