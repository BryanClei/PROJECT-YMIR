<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RRSyncDisplay extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $rr_orders = $this->rr_transaction->flatMap(function ($rr) {
            return $rr->rr_orders;
        });

        $po_items = $this->order->map(function ($po_item) use ($rr_orders) {
            $matching_rr_orders = $rr_orders->where("item_id", $po_item->id);

            return [
                "item_code" => $po_item->item_code,
                "item_name" => $po_item->item_name,
                "supplier" => $this->supplier_id,
                "quantity" => $po_item->quantity,
                "quantity_delivered" => $matching_rr_orders->sum(
                    "quantity_receive"
                ),
                "remaining" =>
                    $po_item->quantity -
                    $matching_rr_orders->sum("quantity_receive"),
                "unit_price" => $po_item->price,
                "total_price" => $po_item->total_price,
                "rr_orders" => $matching_rr_orders
                    ->map(function ($order) {
                        return [
                            "id" => $order->id,
                            "rr_number" => $order->rr_number,
                            "item_name" => $order->item_name,
                            "quantity_receive" => $order->quantity_receive,
                            "remaining" => $order->remaining,
                            "shipment_no" => $order->shipment_no,
                            "delivery_date" => $order->delivery_date,
                            "rr_date" => $order->rr_date,
                            "sync" => $order->sync,
                        ];
                    })
                    ->toArray(),
                "remarks" => $po_item->remarks,
            ];
        });

        return [
            "remarks" => $this->po_description,
            "pr_number" => $this->pr_number,
            "transaction_no" => $this->pr_transaction->transaction_no,
            "po_number" => $this->id,
            "rr_numbers" => $this->rr_transaction->pluck("id"),
            "order" => $po_items->toArray(),
            "cancelled_at" => $this->cancelled_at,
        ];
    }
}
