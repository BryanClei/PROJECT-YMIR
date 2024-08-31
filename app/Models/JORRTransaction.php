<?php

namespace App\Models;

use App\Filters\JORRTransactionFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JORRTransaction extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    protected $connection = "mysql";
    protected string $default_filters = JORRTransactionFilter::class;

    protected $table = "jo_rr_transactions";

    protected $fillable = [
        "jo_po_id",
        "jo_id",
        "received_by",
        "tagging_id",
        "reason",
    ];

    public function jo_po_transactions()
    {
        return $this->belongsTo(JOPOTransaction::class, "jo_po_id", "id");
    }

    public function jo_po_order()
    {
        return $this->belongsTo(JoPoOrders::class, "jo_po_id", "id");
    }

    public function rr_orders()
    {
        return $this->hasMany(
            JORROrders::class,
            "jo_rr_number",
            "id"
        )->withTrashed();
    }
}
