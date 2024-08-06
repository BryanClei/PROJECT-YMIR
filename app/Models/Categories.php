<?php

namespace App\Models;

use App\Filters\CategoriesFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Categories extends Model
{
    use Filterable, HasFactory, SoftDeletes;
    protected $connection = "mysql";
    protected string $default_filters = CategoriesFilter::class;

    protected $fillable = ["name", "code"];
    protected $hidden = ["created_at"];
}
