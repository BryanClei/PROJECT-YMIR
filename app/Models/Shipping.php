<?php

namespace App\Models;

use App\Filters\ShippingFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shipping extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected $connection = "mysql";

    protected string $default_filters = ShippingFilters::class;

    protected $table = "shipping";

    protected $fillable = ["location", "address"];
}
