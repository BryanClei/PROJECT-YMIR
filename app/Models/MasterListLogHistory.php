<?php

namespace App\Models;

use App\Filters\MasterLogsFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MasterListLogHistory extends Model
{
    use HasFactory, Filterable;

    protected $connection = "mysql";

    protected string $default_filters = MasterLogsFilter::class;

    protected $table = "masters_log_history";

    protected $fillable = [
        "module_type",
        "module_name",
        "action",
        "action_by",
        "action_by_name",
        "log_info",
        "previous_data",
        "new_data",
        "ip_address",
        "user_agent",
    ];

    protected $casts = [
        "previous_data" => "array",
        "new_data" => "array",
    ];
}
