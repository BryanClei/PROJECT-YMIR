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
        "jo_id",
        "jo_po_id",
        "action_by",
    ];

    public function users()
    {
        return $this->belongsTo(User::class, "action_by", "id");
    }
}
