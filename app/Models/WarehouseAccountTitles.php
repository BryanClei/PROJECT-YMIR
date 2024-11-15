<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseAccountTitles extends Model
{
    use HasFactory;

    protected $fillable = [
        "warehouse_id",
        "account_title_id",
        "transaction_type",
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function accountTitle()
    {
        return $this->belongsTo(AccountTitle::class);
    }
}
