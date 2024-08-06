<?php

namespace App\Models;

use App\Filters\UomFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Uom extends Model
{
    use Filterable, HasFactory, SoftDeletes;
    protected $connection = "mysql";
    protected string $default_filters = UomFilters::class;

    protected $fillable = ["code", "name", "is_integer"];

    protected $hidden = ["created_at"];
}
