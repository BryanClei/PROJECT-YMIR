<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemWarehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = "mysql";

    protected $table = "item_warehouse";

    protected $fillable = ["item_id", "warehouse_id"];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, "warehouse_id", "id");
    }
}
