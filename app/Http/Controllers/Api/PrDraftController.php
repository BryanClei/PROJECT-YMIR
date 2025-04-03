<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\PrDrafts;
use App\Response\Message;
use App\Models\PrItemDrafts;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Resources\PrDraftResource;
use App\Http\Requests\DraftDisplayRequest;
use App\Http\Requests\PrDrafts\StoreRequest;

class PrDraftController extends Controller
{
    public function index(DraftDisplayRequest $request)
    {
        $user_id = Auth()->user()->id;

        $type = $request->type;

        $drafts = PrDrafts::with("order")
            ->where("user_id", $user_id)
            ->where("type_name", $type)
            ->useFilters()
            ->dynamicPaginate();

        if ($drafts->isEmpty()) {
            return GlobalFunction::notFound(Message::NO_DATA_FOUND);
        }

        PrDraftResource::collection($drafts);

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DRAFT_DISPLAY,
            $drafts
        );
    }
    
    public function store(StoreRequest $request)
    {
        $user_id = Auth()->user()->id;

        $carbon = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");
        $for_po_id = $request->boolean("for_po_only") ? $user_id : null;
        $date_today = $request->boolean("for_po_only") ? $carbon : null;
        $rush = $request->boolean("rush") ? $carbon : null;
        $orders = $request->order;

        $latest_draft = PrDrafts::withTrashed()->max("id") ?? 0;
        $draft_number = $latest_draft + 1;

        $pr_draft = new PrDrafts([
            "pr_draft_id" => $draft_number,
            "pr_description" => $request["pr_description"],
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
            "module_name" => $request->module_name,
            "status" => "Draft",
            "asset" => $request->asset,
            "sgp" => $request->sgp,
            "f1" => $request->f1,
            "f2" => $request->f2,
            "rush" => $rush,
            "for_po_only" => $date_today,
            "for_po_only_id" => $for_po_id,
            "description" => $request->description,
            "supplier_name" => $request->supplier_name,
            "supplier_id" => $request->supplier_id,
        ]);
        $pr_draft->save();

        foreach ($orders as $index => $values) {
            PrItemDrafts::create([
                "pr_draft_id" => $pr_draft->id,
                "item_id" => $request["order"][$index]["item_id"],
                "item_code" => $request["order"][$index]["item_code"],
                "item_name" => $request["order"][$index]["item_name"],
                "uom_id" => $request["order"][$index]["uom_id"],
                "quantity" => $request["order"][$index]["quantity"],
                "unit_price" => $request["order"][$index]["unit_price"],
                "total_price" => $request["order"][$index]["total_price"],
                "remarks" => $request["order"][$index]["remarks"],
                "assets" => $request["order"][$index]["assets"],
                "warehouse_id" => $request["order"][$index]["warehouse_id"],
                "category_id" => $request["order"][$index]["category_id"],
            ]);
        }

        $draft_collect = new PrDraftResource($pr_draft);

        return GlobalFunction::save(
            Message::PURCHASE_REQUEST_DRAFT_SAVE,
            $draft_collect
        );
    }

    public function update(StoreRequest $request, $id)
    {
        $pr_draft = PrDrafts::with("order")->find($id);
        $user_id = Auth()->user()->id;

        if (!$pr_draft) {
            return GlobalFunction::notFound(Message::INVALID_ACTION);
        }

        $carbon = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");
        $for_po_id = $request->boolean("for_po_only") ? $user_id : null;
        $date_today = $request->boolean("for_po_only") ? $carbon : null;
        $rush = $request->boolean("rush") ? $carbon : null;
        $orders = $request->order;

        $draft_orders = $pr_draft->order;

        $pr_draft->update([
            "pr_description" => $request["pr_description"],
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
            "module_name" => $request->module_name,
            "status" => "Draft",
            "asset" => $request->asset,
            "sgp" => $request->sgp,
            "f1" => $request->f1,
            "f2" => $request->f2,
            "rush" => $rush,
            "for_po_only" => $date_today,
            "for_po_only_id" => $for_po_id,
            "description" => $request->description,
            "supplier_name" => $request->supplier_name,
            "supplier_id" => $request->supplier_id,
        ]);

        $newOrders = collect($orders)
            ->pluck("id")
            ->toArray();
        $currentOrders = PrItemDrafts::where("pr_draft_id", $id)
            ->get()
            ->pluck("id")
            ->toArray();

        foreach ($currentOrders as $order_id) {
            if (!in_array($order_id, $newOrders)) {
                PrItemDrafts::where("id", $order_id)->forceDelete();
            }
        }

        foreach ($orders as $index => $values) {
            PrItemDrafts::withTrashed()->updateOrCreate(
                [
                    "id" => $values["id"] ?? null,
                ],
                [
                    "pr_draft_id" => $id,
                    "item_id" => $values["item_id"],
                    "item_code" => $values["item_code"],
                    "item_name" => $values["item_name"],
                    "uom_id" => $values["uom_id"],
                    // "item_stock" => $values["item_stock"],
                    "quantity" => $values["quantity"],
                    "unit_price" => $values["unit_price"],
                    "total_price" => $values["total_price"],
                    "remarks" => $values["remarks"],
                    "assets" => $values["assets"],
                    "warehouse_id" => $values["warehouse_id"],
                    "category_id" => $values["category_id"],
                ]
            );
        }

        $draft_collect = new PrDraftResource($pr_draft->fresh());

        return GlobalFunction::save(
            Message::PURCHASE_REQUEST_DRAFT_UPDATE,
            $draft_collect
        );
    }
}
