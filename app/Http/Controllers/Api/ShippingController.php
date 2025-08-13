<?php

namespace App\Http\Controllers\Api;

use App\Models\Shipping;
use App\Response\Message;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisplayRequest;
use App\Http\Requests\Shipping\StoreRequest;

class ShippingController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $shipping = Shipping::when($status == "inactive", function ($query) {
            $query->onlyTrashed();
        })
            ->useFilters()
            ->dynamicPaginate();

        if ($shipping->isEmpty()) {
            return GlobalFunction::notFound(Message::NO_DATA_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::SHIP_TO_DISPLAY,
            $shipping
        );
    }

    public function show()
    {
    }

    public function store(StoreRequest $request)
    {
        $shipping = Shipping::create([
            "location" => $request->location,
            "address" => $request->address,
        ]);

        return GlobalFunction::save(Message::SHIP_TO_SAVE, $shipping);
    }

    public function update(StoreRequest $request, $id)
    {
        $shipping = Shipping::find($id);

        if (!$shipping) {
            return GlobalFunction::notFound(Message::NO_DATA_FOUND);
        }

        $shipping->update([
            "location" => $request->location,
            "address" => $request->address,
        ]);

        $shipping->refresh();

        return GlobalFunction::responseFunction(
            Message::SHIP_TO_UPDATED,
            $shipping
        );
    }

    public function archived($id)
    {
        $shipping = Shipping::withTrashed()->find($id);

        if (!$shipping) {
            return GlobalFunction::notFound(Message::NO_DATA_FOUND);
        }

        if ($shipping->trashed()) {
            $shipping->restore();
            $message = Message::RESTORE_STATUS;
        } else {
            $shipping->delete();
            $message = Message::ARCHIVE_STATUS;
        }

        return GlobalFunction::responseFunction($message, $shipping);
    }
}
