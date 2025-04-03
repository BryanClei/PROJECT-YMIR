<?php

namespace App\Models;

use App\Filters\POItemsFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class POItems extends Model
{
    use Filterable, HasFactory, SoftDeletes;
    protected string $default_filters = POItemsFilters::class;
    protected $connection = "mysql";
    protected $table = "po_orders";
    protected $fillable = [
        "po_id",
        "reference_no",
        "pr_id",
        "pr_item_id",
        "item_id",
        "item_code",
        "item_name",
        "supplier_id",
        "uom_id",
        "price",
        "item_stock",
        "quantity",
        "quantity_serve",
        "total_price",
        "attachment",
        "buyer_id",
        "buyer_name",
        "remarks",
        "warehouse_id",
        "category_id",
    ];

    protected $casts = [
        "attachment" => "array",
        "remarks" => "json",
    ];

    public function uom()
    {
        return $this->belongsTo(Uom::class, "uom_id", "id");
    }

    public function supplier()
    {
        return $this->belongsTo(Suppliers::class, "supplier_id", "id");
    }

    public function pr_item()
    {
        return $this->belongsTo(PRItems::class, "pr_item_id", "id");
    }

    public function items()
    {
        return $this->belongsTo(Items::class, "item_code", "code");
    }

    public function po_transaction()
    {
        return $this->belongsTo(POTransaction::class, "po_id", "po_number");
    }

    public function category()
    {
        return $this->belongsTo(Categories::class, "category_id", "id");
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, "warehouse_id", "id");
    }

    public function rr_orders()
    {
        return $this->hasMany(RROrders::class, "item_id", "id");
    }

    public function small_tools()
    {
        return $this->hasMany(SmallTools::class, "id", "item_id");
    }
}
