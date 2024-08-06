<?php

namespace App\Models;

use App\Filters\AccountTitleFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountTitle extends Model
{
    use Filterable, HasFactory, SoftDeletes;

    protected string $default_filters = AccountTitleFilters::class;

    protected $fillable = [
        "name",
        "code",
        "account_type_id",
        "account_group_id",
        "account_sub_group_id",
        "financial_statement_id",
        "normal_balance_id",
        "account_title_unit_id",
    ];

    protected $hidden = ["created_at"];
    public function account_type()
    {
        return $this->belongsTo(
            AccountType::class,
            "account_type_id",
            "id"
        )->withTrashed();
    }

    public function account_group()
    {
        return $this->belongsTo(
            AccountGroup::class,
            "account_group_id",
            "id"
        )->withTrashed();
    }
    public function account_sub_group()
    {
        return $this->belongsTo(
            AccountSubGroup::class,
            "account_sub_group_id",
            "id"
        )->withTrashed();
    }
    public function financial_statement()
    {
        return $this->belongsTo(
            FinancialStatement::class,
            "financial_statement_id",
            "id"
        )->withTrashed();
    }
    public function normal_balance()
    {
        return $this->belongsTo(
            NormalBalance::class,
            "normal_balance_id",
            "id"
        )->withTrashed();
    }
    public function account_title_unit()
    {
        return $this->belongsTo(
            AccountTitleUnit::class,
            "account_title_unit_id",
            "id"
        )->withTrashed();
    }
}
