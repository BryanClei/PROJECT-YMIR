<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use Illuminate\Http\Request;
use App\Models\CanvasApprover;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Http\Requests\Canvas\StoreRequest;

class CanvasController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;
        $job_order = CanvasApprover::when($status === "inactive", function (
            $query
        ) {
            $query->onlyTrashed();
        })
            ->useFilters()
            ->latest("updated_at")
            ->dynamicPaginate();

        $is_empty = $job_order->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::APPROVERS_DISPLAY,
            $job_order
        );
    }
    public function store(StoreRequest $request)
    {
        $group = $request->all();

        $count = count($group);

        if ($count == 0) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $newTaggedApproval = collect($group)
            ->pluck("approver_id")
            ->toArray();

        $approver_id = CanvasApprover::whereIn(
            "approver_id",
            $newTaggedApproval
        )
            ->get()
            ->pluck("id")
            ->toArray();

        $currentTaggedApproval = CanvasApprover::get()
            ->pluck("id")
            ->toArray();

        foreach ($currentTaggedApproval as $set_approver_id) {
            if (!in_array($set_approver_id, $approver_id)) {
                CanvasApprover::where("id", $set_approver_id)->delete();
            }
        }

        foreach ($group as $index => $value) {
            CanvasApprover::updateOrCreate(
                [
                    "approver_id" => $value["approver_id"],
                    "approver_name" => $value["approver_name"],
                ],
                [
                    "from_price" => $value["from_price"],
                    "to_price" => $value["to_price"],
                ]
            );
        }
        return GlobalFunction::save(
            Message::APPROVERS_SAVE,
            $request->toArray()
        );
    }
}
