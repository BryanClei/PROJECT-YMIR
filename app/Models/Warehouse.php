<?php

namespace App\Models;

use App\Filters\WarehouseFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Warehouse extends Model
{
    use Filterable, HasFactory, SoftDeletes;

    protected string $default_filters = WarehouseFilters::class;
    protected $table = "warehouses";
    protected $connection = "mysql";
    protected $fillable = ["name", "code", "url", "token"];
    protected $hidden = ["created_at"];
}
