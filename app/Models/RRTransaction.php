<?php

namespace App\Models;

use App\Filters\RRTransactionFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RRTransaction extends Model
{
    use Filterable, HasFactory, SoftDeletes;
    protected $connection = "mysql";
    protected string $default_filters = RRTransactionFilters::class;

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

    // protected $casts = ["remarks" => "json"];

    public function order()
    {
        return $this->hasMany(PRItems::class, "transaction_id", "pr_id");
    }

    public function po_order()
    {
        return $this->hasMany(POItems::class, "po_id", "po_id");
    }

    public function approver_history()
    {
        return $this->hasMany(PrHistory::class, "pr_id", "id");
    }

    public function po_transaction()
    {
        return $this->hasMany(
            POTransaction::class,
            "po_number",
            "po_id"
        )->withTrashed();
    }

    public function pr_transaction()
    {
        return $this->belongsTo(
            PRTransaction::class,
            "pr_id",
            "id"
        )->withTrashed();
    }

    public function rr_orders()
    {
        return $this->hasMany(RROrders::class, "rr_id", "id");
    }

    public function log_history()
    {
        return $this->hasMany(LogHistory::class, "rr_id", "id");
    }
}
