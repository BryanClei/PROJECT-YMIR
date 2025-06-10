<?php

namespace App\Models;

use App\Filters\JrDraftFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JrDrafts extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected string $default_filters = JrDraftFilters::class;

    protected $table = "jr_drafts";

    protected $fillable = [
        "jr_draft_id",
        "jo_description",
        "date_needed",
        "user_id",
        "type_id",
        "type_name",
        "business_unit_id",
        "business_unit_name",
        "company_id",
        "company_name",
        "department_id",
        "department_name",
        "department_unit_id",
        "department_unit_name",
        "location_id",
        "location_name",
        "sub_unit_id",
        "sub_unit_name",
        "account_title_id",
        "account_title_name",
        "assets",
        "module_name",
        "total_price",
        "status",
        "description",
        "reason",
        "approver_id",
        "rush",
        "outside_labor",
        "cap_ex",
        "direct_po",
        "ship_to",
        "helpdesk_id",
    ];

    public function users()
    {
        return $this->belongsTo(User::class, "user_id", "id")->withTrashed();
    }

    public function order()
    {
        return $this->hasMany(
            JrItemDrafts::class,
            "jr_draft_id",
            "jr_draft_id"
        );
    }
}
