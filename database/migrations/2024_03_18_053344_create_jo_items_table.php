<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("jo_items", function (Blueprint $table) {
            $table->increments("id");
            $table->unsignedInteger("jo_transaction_id")->index();
            $table
                ->foreign("jo_transaction_id")
                ->references("id")
                ->on("jo_transactions");
            $table->string("description");
            $table->unsignedInteger("uom_id")->index();
            $table
                ->foreign("uom_id")
                ->references("id")
                ->on("uoms");
            $table->timestamp("po_at")->nullable();
            $table->string("purchase_order_id")->nullable();
            $table->double("quantity");
            $table->double("unit_price")->nullable();
            $table->double("total_price")->nullable();
            $table->string("remarks")->nullable();
            $table->string("attachment")->nullable();
            $table->string("asset")->nullable();
            $table->string("asset_code")->nullable();
            $table->string("helpdesk_id")->nullable();
            $table->string("reference_no")->nullable();
            $table->string("buyer_id")->nullable();
            $table->string("buyer_name")->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("jo_items");
    }
};
