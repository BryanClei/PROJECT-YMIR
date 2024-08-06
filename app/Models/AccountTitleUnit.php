<?php

namespace App\Models;

use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use App\Filters\AccountTitleUnitsFilters;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountTitleUnit extends Model
{
    use Filterable, HasFactory, SoftDeletes;
    protected $connection = "mysql";
    protected string $default_filters = AccountTitleUnitsFilters::class;

    protected $table = "account_title_units";

    protected $fillable = ["name"];

    protected $hidden = ["created_at"];
}
