<?php

namespace App\Models;

use App\Filters\NormalBalanceFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NormalBalance extends Model
{
    use Filterable, HasFactory, SoftDeletes;
    protected $connection = "mysql";
    protected string $default_filters = NormalBalanceFilters::class;

    protected $table = "account_normal_balance";

    protected $fillable = ["name"];

    protected $hidden = ["created_at"];
}
