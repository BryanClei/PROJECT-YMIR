<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountTitleResource extends JsonResource
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
            "updated_at" => $this->updated_at,
            "deleted_at" => $this->deleted_at,
            "account types" => [
                "id" => $this->account_type->id,
                "name" => $this->account_type->name,
                "updated_at" => $this->account_type->updated_at,
                "deleted_at" => $this->account_type->deleted_at,
            ],
            "account groups" => [
                "id" => $this->account_group->id,
                "name" => $this->account_group->name,
                "updated_at" => $this->account_group->updated_at,
                "deleted_at" => $this->account_group->deleted_at,
            ],
            "account sub_groups" => [
                "id" => $this->account_sub_group->id,
                "name" => $this->account_sub_group->name,
                "updated_at" => $this->account_sub_group->updated_at,
                "deleted_at" => $this->account_sub_group->deleted_at,
            ],
            "financial statements" => [
                "id" => $this->financial_statement->id,
                "name" => $this->financial_statement->name,
                "updated_at" => $this->financial_statement->updated_at,
                "deleted_at" => $this->financial_statement->deleted_at,
            ],
            "normal balance" => [
                "id" => $this->normal_balance->id,
                "name" => $this->normal_balance->name,
                "updated_at" => $this->normal_balance->updated_at,
                "deleted_at" => $this->normal_balance->deleted_at,
            ],
            "unit" => [
                "id" => $this->account_title_unit->id,
                "name" => $this->account_title_unit->name,
                "updated_at" => $this->account_title_unit->updated_at,
                "deleted_at" => $this->account_title_unit->deleted_at,
            ],
        ];
    }
}
