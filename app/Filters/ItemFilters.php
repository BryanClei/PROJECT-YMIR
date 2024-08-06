<?php

namespace App\Filters;

use App\Models\Items;
use Essa\APIToolKit\Filters\QueryFilters;

class ItemFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [];

    public function type($type){
        // $items = Items::whereHas("types", );

        $this->builder->whereHas("types", function($query)use($type){
            $query->where("id", $type);
        })->get();
    }
}
