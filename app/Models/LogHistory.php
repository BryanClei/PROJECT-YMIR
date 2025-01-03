<?php

namespace App\Models;

use App\Filters\LogHistoryFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LogHistory extends Model
{
    use HasFactory, Filterable;
    protected $connection = "mysql";
    protected string $default_filters = LogHistoryFilter::class;

    protected $table = "log_history";

    protected $fillable = [
        "activity",
        "pr_id",
        "po_id",
        "rr_id",
        "jo_id",
        "jo_po_id",
        "jo_rr_id",
        "action_by",
    ];

    public function users()
    {
        return $this->belongsTo(User::class, "action_by", "id");
    }

    public function po_transaction()
    {
        return $this->belongsTo(POTransaction::class, "po_id", "id");
    }

    public function rr_transaction()
    {
        return $this->belongsTo(RRTransaction::class, "rr_id", "id");
    }

    public function jo_po_transaction()
    {
        return $this->belongsTo(JOPOTransaction::class, "jo_po_id", "id");
    }

    public function jo_transaction()
    {
        return $this->belongsTo(JobOrderTransaction::class, "jo_id", "id");
    }

    public function pr_transaction()
    {
        return $this->belongsTo(PRTransaction::class, "pr_id", "id");
    }
}
