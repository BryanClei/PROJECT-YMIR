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

    public function uom()
    {
        return $this->belongsTo(UOM::class, "uom_id", "id");
    }

    public function supplier()
    {
        return $this->belongsTo(Suppliers::class, "supplier_id", "id");
    }

    public function pr_item()
    {
        return $this->belongsTo(PrItems::class, "pr_item_id", "id");
    }

    public function items()
    {
        return $this->belongsTo(Items::class, "item_id", "id");
    }

    public function po_transaction()
    {
        return $this->belongsTo(POTransaction::class, "po_id", "po_number");
    }
}
