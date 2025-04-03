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
        return [
            "rr_year_number_id" => $this->rr_year_number_id,
            "rr_number" => $this->id,
            "causer" =>
                $this->user->prefix_id .
                "-" .
                $this->user->id_number .
                " " .
                $this->user->first_name .
                " " .
                $this->user->middle_name .
                " " .
                $this->user->last_name,
            "orders" => collect($this->rr_orders)->map(
                fn($order) => [
                    "transaction_no" =>
                        $order["order"]["po_transaction"]["pr_transaction"][
                            "transaction_no"
                        ],
                    "reference_no" => $order["order"]["reference_no"],
                    "item_name" => $order["order"]["item_name"],
                    "supplier" => $order["order"]["supplier_id"],
                    "quantity" => $order["order"]["quantity"],
                    "quantity_delivered" => $order["order"]["quantity_serve"],
                    "remaining" => $order["remaining"],
                    "unit_price" => $order["order"]["price"],
                    "total_price" => $order["order"]["total_price"],
                    "rr_orders" => [
                        "id" => $order["id"],
                        "pr_id" =>
                            $order["order"]["po_transaction"]["pr_transaction"][
                                "id"
                            ],
                        "pr_year_number_id" =>
                            $order["order"]["po_transaction"]["pr_transaction"][
                                "pr_year_number_id"
                            ],
                        "po_id" => $order["order"]["po_id"],
                        "po_year_number_id" =>
                            $order["order"]["po_transaction"][
                                "po_year_number_id"
                            ],
                        "rr_number" => $order["rr_number"],
                        "item_name" => $order["item_name"],
                        "quantity_received" => $order["quantity_receive"],
                        "remaining" => $order["remaining"],
                        "shipment_no" => $order["shipment_no"],
                        "delivery_date" => $order["delivery_date"],
                        "rr_date" => $order["rr_date"],
                        "sync" => $order["sync"],
                        "remarks" => $order["order"]["remarks"],
                        "initial_credit_id" =>
                            $order["order"]["po_transaction"]["account_title"][
                                "credit_id"
                            ],
                    ],
                ]
            ),
            "cancelled_at" => $this->cancelled_at,
        ];
    }
}
