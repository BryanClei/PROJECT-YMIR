<?php

namespace App\Models;

use App\Filters\RRTransactionV2Filters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RRTransactionV2 extends Model
{
    use Filterable, HasFactory, SoftDeletes;
    protected $connection = "mysql";
    protected string $default_filters = RRTransactionV2Filters::class;

    protected $table = "rr_transactions";

    protected $fillable = [
        "rr_year_number_id",
        "rr_id",
        "po_id",
        "pr_id",
        "received_by",
        "tagging_id",
        "transaction_date",
        "attachment",
        "late_attachment",
        "reason",
    ];

    public function rr_orders()
    {
        return $this->hasMany(RROrders::class, "rr_id", "id");
    }

    public function po_orders()
    {
        return $this->hasMany(POItems::class, "po_id", "id");
    }

    public function po_transaction()
    {
        return $this->hasMany(POTransaction::class, "id", "po_id");
    }

    public function pr_transaction()
    {
        return $this->hasMany(PRTransaction::class, "id", "pr_id");
    }

    public function log_history()
    {
        return $this->hasMany(LogHistory::class, "rr_id", "id");
    }
}
