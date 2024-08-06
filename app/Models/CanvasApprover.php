<?php

namespace App\Models;

use App\Filters\CanvasApproverFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CanvasApprover extends Model
{
    use Filterable, HasFactory;
    protected $connection = "mysql";
    protected string $default_filters = CanvasApproverFilters::class;

    protected $table = "canvas_approver";

    protected $fillable = [
        "approver_id",
        "approver_name",
        "from_price",
        "to_price",
    ];
}
