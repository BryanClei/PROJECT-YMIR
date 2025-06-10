<?php

namespace App\Helpers;

use Carbon\Carbon;
use App\Models\Items;
use App\Models\POItems;
use App\Models\PRItems;
use App\Models\RROrders;
use App\Response\Message;
use App\Models\LogHistory;
use App\Models\POTransaction;
use App\Models\PRTransaction;
use App\Models\RRTransaction;
use App\Functions\GlobalFunction;
use App\Models\AllowablePercentage;

class RRHelperFunctions
{
    public static function checkPRExists($po_transaction, $pr_no)
    {
        $column = $po_transaction->module_name == "Asset" ? "pr_number" : "id";
        return PRTransaction::where($column, $pr_no)->exists();
    }

    public static function checkJRExists($pr_no)
    {
        return PRTransaction::where($pr_no)->exists();
    }

    public static function getPoItems($orders)
    {
        $itemIds = array_column($orders, "item_id");
        return POItems::whereIn("id", $itemIds)
            ->get()
            ->keyBy("id")
            ->toArray();
    }

    // public static function validateQuantities($orders, $po_items)
    // {
    //     foreach ($orders as $order) {
    //         $item_id = $order["item_id"];

    //         if (!isset($po_items[$item_id])) {
    //             return GlobalFunction::invalid(Message::ITEM_NOT_FOUND);
    //         }

    //         $item = $po_items[$item_id];
    //         $quantity_serve = $order["quantity_serve"];
    //         $remaining = $item["quantity"] - $item["quantity_serve"];

    //         $item_allowable = Items::where("code", $item["item_code"])->first();
    //         $allowable = AllowablePercentage::get()->first();

    //         if ($item_allowable->allowable === 0) {
    //             if (
    //                 $item["quantity_serve"] <= 0 &&
    //                 $item["quantity"] < $quantity_serve
    //             ) {
    //                 return GlobalFunction::invalid(
    //                     Message::QUANTITY_VALIDATION
    //                 );
    //             }

    //             if ($item["quantity"] === $item["quantity_serve"]) {
    //                 return GlobalFunction::invalid(
    //                     Message::QUANTITY_VALIDATION
    //                 );
    //             }

    //             if ($remaining < $quantity_serve) {
    //                 return GlobalFunction::invalid(
    //                     Message::QUANTITY_VALIDATION
    //                 );
    //             }
    //         } else {
    //             $allowable_percentage = $allowable->value;

    //             $percent = "." . $allowable_percentage;

    //             $max_allowable = $remaining * $percent;

    //             $total = $remaining + $max_allowable;

    //             if ($order["quantity_serve"] > $total) {
    //                 return GlobalFunction::invalid(
    //                     Message::MAX_QUANTITY_VALIDATION
    //                 );
    //             }
    //         }
    //     }

    //     return true;
    // }

    public static function validateQuantities($orders, $po_items)
    {
        $allowable = AllowablePercentage::first();

        foreach ($orders as $order) {
            $item_id = $order["item_id"];

            // Check if item exists in po_items
            if (!isset($po_items[$item_id])) {
                return GlobalFunction::invalid(Message::ITEM_NOT_FOUND);
            }

            $item = $po_items[$item_id];
            $quantity_serve = $order["quantity_serve"];
            $remaining = $item["quantity"] - $item["quantity_serve"];

            // Handle case where item_code might be null (for Assets)
            $item_allowable = null;
            if (!empty($item["item_code"])) {
                $item_allowable = Items::where(
                    "code",
                    $item["item_code"]
                )->first();
            }

            // If item is an Asset (no item_code) or item not found in Items table
            if ($item_allowable === null) {
                // Basic quantity validation for Assets
                if (
                    $item["quantity_serve"] <= 0 &&
                    $item["quantity"] < $quantity_serve
                ) {
                    return GlobalFunction::invalid(
                        Message::QUANTITY_VALIDATION
                    );
                }

                if ($item["quantity"] === $item["quantity_serve"]) {
                    return GlobalFunction::invalid(
                        Message::QUANTITY_VALIDATION
                    );
                }

                if ($remaining < $quantity_serve) {
                    return GlobalFunction::invalid(
                        Message::QUANTITY_VALIDATION
                    );
                }

                continue; // Skip allowable percentage check for Assets
            }

            // Regular item validation with allowable percentage
            if ($item_allowable->allowable === 0) {
                if (
                    $item["quantity_serve"] <= 0 &&
                    $item["quantity"] < $quantity_serve
                ) {
                    return GlobalFunction::invalid(
                        Message::QUANTITY_VALIDATION
                    );
                }

                if ($item["quantity"] === $item["quantity_serve"]) {
                    return GlobalFunction::invalid(
                        Message::QUANTITY_VALIDATION
                    );
                }

                if ($remaining < $quantity_serve) {
                    return GlobalFunction::invalid(
                        Message::QUANTITY_VALIDATION
                    );
                }
            } else {
                $allowable_percentage = $allowable->value;
                $percent = "." . $allowable_percentage;
                $max_allowable = $remaining * $percent;

                $total = $remaining + $max_allowable;
                if ($order["quantity_serve"] > $total) {
                    return GlobalFunction::invalid(
                        Message::MAX_QUANTITY_VALIDATION
                    );
                }
            }
        }

        return true;
    }

    public static function createRRTransaction(
        $po_transaction,
        $user_id,
        $tagging_id,
        $attachment
    ) {
        $current_year = date("Y");
        $latest_rr = RRTransaction::withTrashed()
            ->where("rr_year_number_id", "like", $current_year . "-RR-%")
            ->orderByRaw(
                "CAST(SUBSTRING_INDEX(rr_year_number_id, '-', -1) AS UNSIGNED) DESC"
            )
            ->first();

        $new_number = $latest_rr
            ? (int) explode("-", $latest_rr->rr_year_number_id)[2] + 1
            : 1;
        $rr_year_number_id =
            $current_year . "-RR-" . str_pad($new_number, 3, "0", STR_PAD_LEFT);

        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i:s");

        $rr_transaction = new RRTransaction([
            "rr_year_number_id" => $rr_year_number_id,
            "pr_id" => $po_transaction->pr_number,
            "po_id" => $po_transaction->po_number,
            "received_by" => $user_id,
            "tagging_id" => $tagging_id,
            "transaction_date" => $date_today,
            "attachment" => $attachment,
        ]);

        $rr_transaction->save();
        return $rr_transaction;
    }

    public static function processOrders(
        $orders,
        $po_items,
        $rr_transaction,
        $po_transactions
    ) {
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");

        $itemDetails = [];
        foreach ($orders as $index => $order) {
            $item_id = $order["item_id"];
            $quantity_serve = $order["quantity_serve"];
            $po_no = $order["po_no"];

            $po_transaction = $po_transactions->firstWhere("po_number", $po_no);

            $original_quantity = $po_items[$item_id]["quantity"];
            $original_quantity_serve = $po_items[$item_id]["quantity_serve"];
            $remaining =
                $original_quantity -
                ($original_quantity_serve + $quantity_serve);

            $itemDetails[] = [
                "item_name" => $order["item_name"],
                "quantity_receive" => $quantity_serve,
                "remaining" => $remaining,
                "date" => $date_today,
                "po_no" => $po_no,
            ];

            self::createRROrder(
                $rr_transaction,
                $order,
                $remaining,
                $index,
                $po_transaction
            );

            self::updatePOItem(
                $item_id,
                $original_quantity_serve,
                $quantity_serve
            );
        }
        return $itemDetails;
    }
    
    private static function createRROrder(
        $rr_transaction,
        $order,
        $remaining,
        $orderIndex,
        $po_transaction = null
    ) {
        $filenames = self::processAttachments(
            $order["attachment"] ?? [],
            $rr_transaction->id,
            $orderIndex
        );

        RROrders::create([
            "rr_number" => $rr_transaction->id,
            "rr_id" => $rr_transaction->id,
            "pr_id" => $po_transaction ? $po_transaction->pr_number : null,
            "po_id" => $order["po_no"],
            "item_id" => $order["item_id"],
            "item_code" => $order["item_code"],
            "item_name" => $order["item_name"],
            "quantity_receive" => $order["quantity_serve"],
            "remaining" => $remaining,
            "shipment_no" => $order["shipment_no"],
            "delivery_date" => $order["delivery_date"],
            "rr_date" => $order["rr_date"],
            "attachment" => json_encode($filenames),
            "sync" => 0,
            "f_tagged" => 0,
        ]);
    }

    private static function updatePOItem(
        $item_id,
        $original_quantity_serve,
        $quantity_serve
    ) {
        $po_item = POItems::find($item_id);
        $po_item->update([
            "quantity_serve" => $original_quantity_serve + $quantity_serve,
        ]);

        $pr_item = PRItems::find($po_item->pr_item_id);
        if ($pr_item) {
            $new_partial_received = $pr_item->partial_received + $quantity_serve;
            $new_remaining_qty = $pr_item->quantity - $new_partial_received;
            
            $pr_item->update([
                "partial_received" => $new_partial_received,
                "remaining_qty" => $new_remaining_qty, 
            ]);
        }
    }

    private static function processAttachments(
        $attachments,
        $rr_id,
        $orderIndex
    ) {
        $filenames = [];
        if (!empty($attachments)) {
            foreach ($attachments as $fileIndex => $file) {
                $info = pathinfo(basename($file));
                $filename = "{$info["filename"]}_rr_id_{$rr_id}_item_{$orderIndex}_file_{$fileIndex}.{$info["extension"]}";
                $filenames[] = $filename;
            }
        }
        return $filenames;
    }

    public static function createLogHistory(
        $rr_transaction,
        $user_id,
        $itemDetails
    ) {
        $itemList = array_map(function ($item) {
            return "{$item["item_name"]} (Received: {$item["quantity_receive"]}, Remaining: {$item["remaining"]}, PO: {$item["po_no"]}, Date Received: {$item["date"]})";
        }, $itemDetails);

        $activityDescription =
            "Received Receipt ID: {$rr_transaction->id} has been received by UID: {$user_id}. Items received: " .
            implode(", ", $itemList);

        LogHistory::create([
            "activity" => $activityDescription,
            "rr_id" => $rr_transaction->id,
            "action_by" => $user_id,
        ]);
    }

    public static function checkRRExists($rr_number)
    {
        return RRTransaction::where("id", $rr_number)->first();
    }

    public static function validateQuantityReceiving($orders, $request)
    {
        foreach ($orders as $index => $values) {
            $rr_orders_id = $request["order"][$index]["item_id"];
            $quantity_receiving = $request["order"][$index]["quantity_serve"];
            $po_order = POItems::where("id", $rr_orders_id)
                ->get()
                ->first();
            $remaining = $po_order->quantity - $po_order->quantity_serve;

            if ($quantity_receiving > $remaining) {
                return GlobalFunction::invalid(Message::QUANTITY_VALIDATION);
            }
        }
    }

    public static function createRROrderAndUpdatePOItem(
        $rr_orders_id,
        $request,
        $po_orders,
        $index,
        $rr_number,
        &$itemDetails
    ) {
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d H:i");
        $remaining = $po_orders->quantity - $po_orders->quantity_serve;

        $itemDetails[] = [
            "item_name" => $request["order"][$index]["item_name"],
            "quantity_receive" => $request["order"][$index]["quantity_serve"],
            "remaining" =>
                $remaining - $request["order"][$index]["quantity_serve"],
            "date" => $date_today,
            "po_no" => $request["order"][$index]["po_no"],
        ];

        $add_previous = RROrders::create([
            "rr_number" => $rr_number,
            "rr_id" => $rr_number,
            "pr_id" => $request["order"][$index]["pr_no"],
            "po_id" => $request["order"][$index]["po_no"],
            "item_id" => $rr_orders_id,
            "item_name" => $request["order"][$index]["item_name"],
            "item_code" => $request["order"][$index]["item_code"],
            "quantity_receive" => $request["order"][$index]["quantity_serve"],
            "remaining" =>
                $remaining - $request["order"][$index]["quantity_serve"],
            "shipment_no" => $request["order"][$index]["shipment_no"],
            "delivery_date" => $request["order"][$index]["delivery_date"],
            "rr_date" => $request["order"][$index]["rr_date"],
            "sync" => 0,
            "f_tagged" => 0,
        ]);

        $po_orders->update([
            "quantity_serve" =>
                $po_orders->quantity_serve +
                $request["order"][$index]["quantity_serve"],
        ]);

        return $add_previous;
    }

    public static function logRRTransaction(
        $rr_transaction,
        $itemDetails,
        $user_id
    ) {
        $itemList = array_map(function ($item) {
            return "{$item["item_name"]} (Received: {$item["quantity_receive"]}, Remaining: {$item["remaining"]}, Date Received: {$item["date"]})";
        }, $itemDetails);

        $activityDescription =
            "Received Receipt ID: {$rr_transaction->id} has been received by UID: {$user_id}. Items received: " .
            implode(", ", $itemList);

        LogHistory::create([
            "activity" => $activityDescription,
            "rr_id" => $rr_transaction->id,
            "action_by" => $user_id,
        ]);
    }
}
