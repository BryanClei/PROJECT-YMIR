<?php

namespace App\Models;

use App\Models\JORROrders;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JORROrders extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    protected $connection = "mysql";
    protected $table = "jo_rr_order";

    protected $fillable = [
        "jo_rr_number",
        "jo_rr_id",
        "jo_po_id",
        "jo_id",
        "jo_item_id",
        "description",
        "quantity_receive",
        "remaining",
        "shipment_no",
        "delivery_date",
        "rr_date",
    ];

    public function jo_rr_transaction()
    {
        return $this->belongsTo(
            JORRTransaction::class,
            "jo_rr_number",
            "id"
        )->withTrashed();
    }

    public function jo_po_transaction()
    {
        return $this->belongsTo(JOPOTransaction::class, "jo_po_id", "id");
    }

    public function jo_transaction()
    {
        return $this->belongsTo(JobOrderTransaction::class, "jo_id", "id");
    }

    public function order()
    {
        return $this->belongsTo(JoPoOrders::class, "jo_item_id", "id");
    }
}
