<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssetsItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "asset_item";

    protected $fillable = ["item_id", "small_tools_id", "code", "name"];
}
