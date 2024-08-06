<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PRItems2 extends Model
{
    use HasFactory;
    protected $connection = "db_bridge";

    protected $table = "pr_items";

    protected $guarded = [];
}
