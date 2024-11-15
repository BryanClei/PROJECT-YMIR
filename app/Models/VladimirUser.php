<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VladimirUser extends Model
{
    use HasFactory;
    protected $table = "users";
    protected $connection = "vladimirDB";

    protected $hidden = ["password"];

    public function prTransaction()
    {
        return $this->hasOne(PrTransaction::class, "user_id", "id");
    }
}
