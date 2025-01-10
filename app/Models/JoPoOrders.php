<?php

namespace App\Models;

use App\Filters\JoPoOrderFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JoPoOrders extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    protected $connection = "mysql";
    protected $table = "jo_po_orders";
    protected string $default_filters = JoPoOrderFilters::class;
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
        "asset_code",
        "helpdesk_id",
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

    public function assets()
    {
        return $this->belongsTo(Assets::class, "asset", "id");
    }

    public function rr_orders()
    {
        return $this->hasMany(RROrders::class, "id", "jo_item_id");
    }

    public function jr_orders()
    {
        return $this->hasMany(JobOrder::class, "id", "jo_item_id");
    }

    public function jo_po_transaction()
    {
        return $this->belongsTo(JOPOTransaction::class, "jo_po_id", "id");
    }
}
