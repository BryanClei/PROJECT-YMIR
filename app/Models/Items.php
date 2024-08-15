<?php

namespace App\Models;

use App\Models\Type;
use App\Filters\ItemFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Items extends Model
{
    use Filterable, HasFactory, SoftDeletes;
    protected $connection = "mysql";
    protected string $default_filters = ItemFilters::class;

    protected $fillable = [
        "name",
        "code",
        "uom_id",
        "category_id",
        "type",
        "warehouse_id",
        "allowable",
    ];
    protected $hidden = ["created_at"];

    public function uom()
    {
        return $this->belongsTo(Uom::class, "uom_id", "id");
    }
    public function category()
    {
        return $this->belongsTo(Categories::class, "category_id", "id");
    }
    public function types()
    {
        return $this->belongsTo(Type::class, "type", "id");
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, "warehouse_id", "id");
    }
}
