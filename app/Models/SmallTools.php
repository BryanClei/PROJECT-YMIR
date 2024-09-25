<?php

namespace App\Models;

use App\Filters\SmallToolsFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmallTools extends Model
{
    use Filterable, HasFactory, SoftDeletes;

    protected string $default_filters = SmallToolsFilters::class;

    protected $table = "small_tools";

    protected $fillable = ["code", "name"];
}
