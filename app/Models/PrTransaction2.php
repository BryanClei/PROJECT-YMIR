<?php

namespace App\Models;

use App\Filters\PRTransactionFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PrTransaction2 extends Model
{
    use HasFactory, Filterable;

    protected string $default_filters = PRTransactionFilters::class;

    protected $connection = "db_bridge";

    protected $table = "pr_transactions";

    protected $guarded = [];

    public function users()
    {
        return $this->belongsTo(User::class, "user_id", "id")->withTrashed();
    }

    // public function order()
    // {
    //     return $this->hasMany(PRItems::class, "transaction_id", "id");
    // }

    public function approver_history()
    {
        return $this->hasMany(PrHistory::class, "pr_id", "id");
    }

    public function po_transaction()
    {
        return $this->hasMany(
            POTransaction::class,
            "pr_number",
            "pr_number"
        )->withTrashed();
    }

    public function rr_transactions()
    {
        return $this->hasMany(RRTransaction::class, "pr_id", "id");
    }

    public function assets()
    {
        return $this->belongsTo(Assets::class, "asset", "id");
    }

    public function log_history()
    {
        return $this->hasMany(LogHistory::class, "pr_id", "id");
    }

    public function order()
    {
        return $this->hasMany(PRItems2::class, "transaction_id", "id");
    }
}
