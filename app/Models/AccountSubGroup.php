<?php

namespace App\Models;

use App\Filters\AccountSubGroupFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountSubGroup extends Model
{
    use Filterable, HasFactory, SoftDeletes;

    protected string $default_filters = AccountSubGroupFilters::class;

    protected $fillable = ["name"];

    protected $hidden = ["created_at"];
}
