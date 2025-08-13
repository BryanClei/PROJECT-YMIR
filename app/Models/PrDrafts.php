<?php

namespace App\Models;

use App\Filters\PrDraftFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PrDrafts extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected string $default_filters = PrDraftFilter::class;

    protected $table = "pr_drafts";

    protected $fillable = [
        "pr_draft_id",
        "pr_description",
        "date_needed",
        "user_id",
        "type_id",
        "type_name",
        "one_charging_id",
        "one_charging_sync_id",
        "one_charging_code",
        "one_charging_name",
        "business_unit_id",
        "business_unit_code",
        "business_unit_name",
        "company_id",
        "company_code",
        "company_name",
        "department_id",
        "department_code",
        "department_name",
        "department_unit_id",
        "department_unit_code",
        "department_unit_name",
        "location_id",
        "location_code",
        "location_name",
        "sub_unit_id",
        "sub_unit_code",
        "sub_unit_name",
        "account_title_id",
        "account_title_name",
        "supplier_id",
        "supplier_name",
        "module_name",
        "status",
        "asset_code",
        "cap_ex",
        "helpdesk_id",
        "asset",
        "sgp",
        "f1",
        "f2",
        "rush",
        "ship_to_id",
        "ship_to_name",
        "pcf_remarks",
        "place_order",
        "for_po_only",
        "for_po_only_id",
        "for_marketing",
    ];

    public function users()
    {
        return $this->belongsTo(User::class, "user_id", "id")->withTrashed();
    }

    public function order()
    {
        return $this->hasMany(PrItemDrafts::class, "pr_draft_id", "id");
    }

    public function assets()
    {
        return $this->belongsTo(Assets::class, "asset", "id");
    }
}
