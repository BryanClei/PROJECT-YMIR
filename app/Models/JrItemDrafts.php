<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JrItemDrafts extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "jr_draft_items";

    protected $fillable = [
        "jr_draft_id",
        "description",
        "uom_id",
        "quantity",
        "unit_price",
        "total_price",
        "remarks",
        "attachment",
        "asset",
        "asset_code",
        "helpdesk_id",
        "reference_no",
        "buyer_id",
        "buyer_name",
    ];

    public function jr_draft()
    {
        return $this->belongsTo(JrDraft::class, "jr_draft_id", "jr_draft_id");
    }

    public function asset()
    {
        return $this->belongsTo(Assets::class, "asset", "id");
    }
}
