<?php

namespace App\Models;

use App\Filters\AssetsFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Assets extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    protected $connection = "mysql";
    protected string $default_filters = AssetsFilter::class;

    protected $table = "assets";

    protected $fillable = ["name", "tag_number"];
}
