<?php

namespace App\Http\Controllers\Api;

use App\Models\Credit;
use App\Response\Message;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Http\Resources\CreditResource;
use App\Http\Requests\Credit\StoreRequest;
use App\Http\Requests\Credit\ImportRequest;

class CreditController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;
        $credit = Credit::when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })
            ->useFilters()
            ->dynamicPaginate();

        if ($credit->isEmpty()) {
            return GlobalFunction::notFound(Message::NO_DATA_FOUND);
        }

        CreditResource::collection($credit);

        return GlobalFunction::responseFunction(
            Message::CREDIT_DISPLAY,
            $credit
        );
    }

    public function store(StoreRequest $request)
    {
        $credit_name = $request->name;
        $code = $request->code;

        $credit_query = Credit::create([
            "name" => $credit_name,
            "code" => $code,
        ]);

        $new = new CreditResource($credit_query);

        return GlobalFunction::save(Message::CREDIT_SAVE, $new);
    }

    public function update(StoreRequest $request, $id)
    {
        $credit_query = Credit::find($id);

        if (!$credit_query) {
            return GlobalFunction::notFound(Message::NO_DATA_FOUND);
        }

        $credit_query->update([
            "name" => $request->name,
            "code" => $request->code,
        ]);

        $new = new CreditResource($credit_query);

        return GlobalFunction::responseFunction(Message::CREDIT_UPDATE, $new);
    }

    public function destroy($id)
    {
        $credit = Credit::where("id", $id)
            ->withTrashed()
            ->get();

        if ($credit->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $credit = Credit::withTrashed()->find($id);
        $is_active = Credit::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $credit->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $credit->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $credit);
    }

    public function import(ImportRequest $request)
    {
        $import = $request->all();

        foreach ($import as $index) {
            $credit_query = Credit::create([
                "name" => $index["name"],
                "code" => $index["code"],
            ]);
        }

        return GlobalFunction::save(Message::CREDIT_SAVE, $import);
    }
}
