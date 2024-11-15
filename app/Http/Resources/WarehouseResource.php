<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
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
            "id" => $this->id,
            "name" => $this->name,
            "code" => $this->code,
            "url" => $this->url,
            "token" => $this->token,
            "warehouse_account_titles" => $this->whenLoaded(
                "warehouseAccountTitles",
                function () {
                    return $this->warehouseAccountTitles->map(function ($wat) {
                        return [
                            "id" => $wat->id,
                            "account_title" => [
                                "id" => $wat->accountTitle->id,
                                "name" => $wat->accountTitle->name,
                            ],
                            "transaction_type" => $wat->transaction_type,
                            "created_at" => $wat->created_at,
                            "updated_at" => $wat->updated_at,
                        ];
                    });
                }
            ),
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
