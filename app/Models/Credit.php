<?php

namespace App\Models;

use App\Filters\CreditFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Credit extends Model
{
    use Filterable, HasFactory, SoftDeletes;
    protected string $default_filters = CreditFilters::class;
    protected $connection = "mysql";
    protected $table = "credit";
    protected $fillable = ["name", "code"];
    protected $hidden = ["created_at"];
}
