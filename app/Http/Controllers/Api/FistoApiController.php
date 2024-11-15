<?php

namespace App\Http\Controllers\Api;

use App\Response\Message;
use Illuminate\Http\Request;
use App\Models\POTransaction;
use App\Models\RRTransaction;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;

class FistoApiController extends Controller
{
    public function index()
    {
        $rr_orders = RRTransaction::with(
            "rr_orders",
            "rr_orders.order.uom",
            "po_transaction.company",
            "po_transaction.department",
            "po_transaction.department_unit",
            "po_transaction.sub_unit",
            "po_transaction.location",
            "po_transaction.account_title",
            "po_transaction.account_title.account_type",
            "po_transaction.account_title.account_group",
            "po_transaction.account_title.account_sub_group",
            "po_transaction.account_title.financial_statement"
        )
            ->useFilters()
            ->dynamicPaginate();

        $is_empty = $rr_orders->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        return GlobalFunction::responseFunction(
            Message::PURCHASE_REQUEST_DISPLAY,
            $rr_orders
        );
    }
}
