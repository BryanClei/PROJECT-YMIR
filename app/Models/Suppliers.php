<?php

namespace App\Models;

use App\Filters\SuppliersFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Suppliers extends Model
{
    use Filterable, HasFactory, SoftDeletes;
    protected $connection = "mysql";
    protected string $default_filters = SuppliersFilters::class;

    protected $fillable = [
        "code",
        "name",
        "term",
        "type",
        "address_1",
        "address_2",
    ];

    protected $hidden = ["created_at"];
}
