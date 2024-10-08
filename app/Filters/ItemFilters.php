<?php

namespace App\Filters;

use App\Models\Items;
use Essa\APIToolKit\Filters\QueryFilters;

class ItemFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [
        "code",
        "name",
        "uom_id",
        "category_id",
        "type",
    ];

    public function type($type)
    {
        $this->builder
            ->whereHas("types", function ($query) use ($type) {
                $query->where("id", $type);
            })
            ->get();
    }

    public function warehouse_id($warehouse_id)
    {
        $this->builder->whereHas("warehouse", function ($query) use (
            $warehouse_id
        ) {
            $query->where("warehouse_id", $warehouse_id);
        });
    }

    public function vladimir($vladimir)
    {
        $this->builder->when($vladimir == "sync", function ($query) use (
            $vladimir
        ) {
            $query->withTrashed();
        });
    }
}
