<?php

namespace App\Models;

use App\Filters\RROrdersFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RROrders extends Model
{
    use Filterable, HasFactory, SoftDeletes;

    protected string $default_filters = RROrdersFilters::class;
    protected $connection = "mysql";
    protected $table = "rr_orders";

    protected $fillable = [
        "rr_number",
        "rr_id",
        "item_id",
        "item_code",
        "item_name",
        "quantity_receive",
        "remaining",
        "shipment_no",
        "delivery_date",
        "rr_date",
        "sync",
    ];

    public function pr_items()
    {
        return $this->hasMany(PRItems::class, "id", "item_id");
    }

    public function po_transaction()
    {
        return $this->belongsTo(POTransaction::class, "po_number", "id");
    }
    public function rr_transaction()
    {
        return $this->belongsTo(RRTransaction::class, "rr_id", "id");
    }

    public function order()
    {
        return $this->belongsTo(POItems::class, "item_id", "id");
    }
}
