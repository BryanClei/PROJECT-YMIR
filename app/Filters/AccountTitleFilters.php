<?php

namespace App\Filters;

use App\Models\AccountType;
use Essa\APIToolKit\Filters\QueryFilters;

class AccountTitleFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = ["name", "code"];

    public function vladimir($vladimir)
    {
        $this->builder->when($vladimir == "sync", function ($query) use (
            $vladimir
        ) {
            $query->withTrashed();
        });
    }

    public function type($type)
    {
        $accountTypeId = AccountType::where("name", $type)->value("id");

        if ($accountTypeId) {
            $this->builder->where("account_type_id", $accountTypeId);
        }
    }

    public function request_type($request_type)
    {
        $type = $request_type;

        $this->builder->where("request_type", "LIKE", "%" . $type . "%");
    }
}
