<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\JrDrafts;
use App\Response\Message;
use App\Models\JrItemDrafts;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Resources\JrDraftResource;
use App\Http\Requests\DraftDisplayRequest;

class JrDraftController extends Controller
{
    public function index(DraftDisplayRequest $request)
    {
        $user_id = Auth()->user()->id;

        $drafts = JrDrafts::with("order")
            ->where("user_id", $user_id)
            ->useFilters()
            ->dynamicPaginate();

        if ($drafts->isEmpty()) {
            return GlobalFunction::notFound(Message::NO_DATA_FOUND);
        }

        JrDraftResource::collection($drafts);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DRAFT_DISPLAY,
            $drafts
        );
    }

    public function store(Request $request)
    {
        $user_id = Auth()->user()->id;
        $orders = $request->order;
        $dateToday = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");
        $for_po_id = $request->boolean("for_po_only") ? $user_id : null;
        $date_today = $request->boolean("for_po_only") ? $dateToday : null;
        $rush = $request->boolean("rush") ? $dateToday : null;

        $latest_jr_number = JrDrafts::withTrashed()->max("jr_draft_id") ?? 0;
        $increment = $latest_jr_number + 1;

        $jr_draft = new JrDrafts([
            "jr_draft_id" => $increment,
            "jo_description" => $request->jo_description,
            "rush" => $rush,
            "helpdesk_id" => $request->helpdesk_id,
            "for_po_only" => $date_today,
            "for_po_only_id" => $for_po_id,
            "direct_po" => $dateToday,
            "outside_labor" => $request->outside_labor,
            "cap_ex" => $request->cap_ex,
            "date_needed" => $request->date_needed,
            "user_id" => $user_id,
            "type_id" => $request->type_id,
            "type_name" => $request->type_name,
            "business_unit_id" => $request->business_unit_id,
            "business_unit_name" => $request->business_unit_name,
            "company_id" => $request->company_id,
            "company_name" => $request->company_name,
            "department_id" => $request->department_id,
            "department_name" => $request->department_name,
            "department_unit_id" => $request->department_unit_id,
            "department_unit_name" => $request->department_unit_name,
            "location_id" => $request->location_id,
            "location_name" => $request->location_name,
            "sub_unit_id" => $request->sub_unit_id,
            "sub_unit_name" => $request->sub_unit_name,
            "account_title_id" => $request->account_title_id,
            "account_title_name" => $request->account_title_name,
            "assets" => $request->asset,
            "description" => $request->description,
            "module_name" => "Job Order",
            "status" => "Draft",
            "ship_to" => $request->ship_to,
        ]);

        $jr_draft->save();

        foreach ($orders as $index => $values) {
            $job_item = JrItemDrafts::create([
                "jr_draft_id" => $jr_draft->id,
                "description" => $values["description"],
                "uom_id" => $values["uom_id"],
                "quantity" => $values["quantity"],
                "unit_price" => $values["unit_price"],
                "total_price" => $values["unit_price"] * $values["quantity"],
                "remarks" => $values["remarks"],
                "asset" => $values["asset"],
                "asset_code" => $values["asset_code"],
                "helpdesk_id" => $values["helpdesk_id"],
            ]);
        }

        $draft_collect = new JrDraftResource($jr_draft);

        return GlobalFunction::save(
            Message::PURCHASE_REQUEST_DRAFT_SAVE,
            $draft_collect
        );
    }

    public function update(Request $request, $id)
    {
        $jr_draft = JrDrafts::with("order")->find($id);
        $user_id = Auth()->user()->id;
        if (!$jr_draft) {
            return GlobalFunction::notFound(Message::INVALID_ACTION);
        }
        $carbon = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");
        $for_po_id = $request->boolean("for_po_only") ? $user_id : null;
        $date_today = $request->boolean("for_po_only") ? $carbon : null;
        $rush = $request->boolean("rush") ? $carbon : null;
        $orders = $request->order;
        $draft_orders = $jr_draft->order;
        $jr_draft->update([
            "jo_description" => $request["jo_description"],
            "date_needed" => $request["date_needed"],
            "user_id" => $user_id,
            "type_id" => $request->type_id,
            "type_name" => $request->type_name,
            "business_unit_id" => $request->business_unit_id,
            "business_unit_name" => $request->business_unit_name,
            "company_id" => $request->company_id,
            "company_name" => $request->company_name,
            "department_id" => $request->department_id,
            "department_name" => $request->department_name,
            "department_unit_id" => $request->department_unit_id,
            "department_unit_name" => $request->department_unit_name,
            "location_id" => $request->location_id,
            "location_name" => $request->location_name,
            "sub_unit_id" => $request->sub_unit_id,
            "sub_unit_name" => $request->sub_unit_name,
            "account_title_id" => $request->account_title_id,
            "account_title_name" => $request->account_title_name,
            "module_name" => "Job Order",
            "status" => "Draft",
            "assets" => $request->asset,
            "rush" => $rush,
            "for_po_only" => $date_today,
            "for_po_only_id" => $for_po_id,
            "direct_po" => $carbon,
            "outside_labor" => $request->outside_labor,
            "cap_ex" => $request->cap_ex,
            "helpdesk_id" => $request->helpdesk_id,
            "description" => $request->description,
            "ship_to" => $request->ship_to,
        ]);
        $newOrders = collect($orders)
            ->pluck("id")
            ->toArray();
        $currentOrders = JrItemDrafts::where("jr_draft_id", $id)
            ->get()
            ->pluck("id")
            ->toArray();
        foreach ($currentOrders as $order_id) {
            if (!in_array($order_id, $newOrders)) {
                JrItemDrafts::where("id", $order_id)->forceDelete();
            }
        }
        foreach ($orders as $index => $values) {
            JrItemDrafts::withTrashed()->updateOrCreate(
                [
                    "id" => $values["id"] ?? null,
                ],
                [
                    "jr_draft_id" => $id,
                    "description" => $values["description"],
                    "uom_id" => $values["uom_id"],
                    "quantity" => $values["quantity"],
                    "unit_price" => $values["unit_price"],
                    "total_price" =>
                        $values["unit_price"] * $values["quantity"],
                    "remarks" => $values["remarks"] ?? null,
                    "asset" => $values["asset"] ?? null,
                    "asset_code" => $values["asset_code"] ?? null,
                    "helpdesk_id" => $values["helpdesk_id"] ?? null,
                ]
            );
        }

        $draft_collect = new JrDraftResource($jr_draft->fresh());

        return GlobalFunction::save(
            Message::PURCHASE_REQUEST_DRAFT_UPDATE,
            $draft_collect
        );
    }
}
