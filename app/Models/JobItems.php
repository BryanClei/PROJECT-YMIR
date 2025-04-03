<?php

namespace App\Models;

use App\Filters\JobItemsFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobItems extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    protected string $default_filters = JobItemsFilters::class;
    protected $connection = "mysql";
    protected $table = "jo_items";
    protected $fillable = [
        "jo_transaction_id",
        "description",
        "uom_id",
        "po_at",
        "purchase_order_id",
        "quantity",
        "unit_price",
        "total_price",
        "remarks",
        "attachment",
        "asset",
        "asset_code",
        "helpdesk_id",
        "reference_no",
        "buyer_id",
        "buyer_name",
    ];
    protected $hidden = ["created_at"];

    protected $casts = ["attachment" => "array"];

    public function transaction()
    {
        return $this->belongsTo(JobOrderTransaction::class, "jo_transaction_id", "id");
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class, "uom_id", "id");
    }

    public function assets()
    {
        return $this->belongsTo(Assets::class, "asset", "id");
    }

    public function jo_po_orders()
    {
        return $this->belongsTo(JoPoOrders::class, "id", "jo_item_id");
    }
}
