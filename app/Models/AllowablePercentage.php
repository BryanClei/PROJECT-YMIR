<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AllowablePercentage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "allowable_percentage";

    protected $fillable = ["value"];
}
