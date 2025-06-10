<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use App\Models\RRDateSetup;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Resources\RRDateSetupResource;
use App\Http\Requests\RRDateSetup\StoreRequest;

class RRDateSetupController extends Controller
{
    public function index()
    {
        $calendar_setup = RRDateSetup::dynamicPaginate();

        if ($calendar_setup->isEmpty()) {
            return GlobalFunction::notFound(Message::NO_DATA_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::CALENDAR_SETUP,
            $calendar_setup
        );
    }

    public function store(StoreRequest $request)
    {
        $calendar_setup = RRDateSetup::create([
            "setup_name" => $request->setup_name,
            "previous_days" => $request->previous_days,
            "forward_days" => $request->forward_days,
            "previous_month" => $request->previous_month,
            "threshold_days" => $request->threshold_days,
        ]);

        $created_setup = new RRDateSetupResource($calendar_setup);

        return GlobalFunction::save(
            Message::CALENDAR_SETUP_SAVE,
            $created_setup
        );
    }

    public function update(StoreRequest $request, $id)
    {
        $calendar_setup = RRDateSetup::find($id);

        if (!$calendar_setup) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $calendar_setup->update([
            "setup_name" => $request->setup_name,
            "previous_days" => $request->previous_days,
            "forward_days" => $request->forward_days,
            "previous_month" => $request->previous_month,
            "threshold_days" => $request->threshold_days,
        ]);

        $updated_setup = new RRDateSetupResource($calendar_setup);

        return GlobalFunction::responseFunction(
            Message::CALENDAR_SETUP_UPDATED,
            $updated_setup
        );
    }

    public function destroy($id)
    {
        $calendar_setup = RRDateSetup::withTrashed()->find($id);

        if (!$calendar_setup) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        if (!$calendar_setup->deleted_at) {
            $calendar_setup->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $calendar_setup->restore();
            $message = Message::RESTORE_STATUS;
        }

        $updated_setup = new RRDateSetupResource($calendar_setup);

        return GlobalFunction::responseFunction($message, $updated_setup);
    }
}
