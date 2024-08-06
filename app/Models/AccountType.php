<?php

namespace App\Models;

use App\Filters\AccountTypeFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountType extends Model
{
    use Filterable, HasFactory, SoftDeletes;
    protected $connection = "mysql";
    protected string $default_filters = AccountTypeFilters::class;

    protected $fillable = ["name"];
    protected $hidden = ["created_at"];
}
