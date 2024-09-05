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
        Schema::create("pr_items", function (Blueprint $table) {
            $table->increments("id");
            $table->unsignedInteger("transaction_id")->index();
            $table
                ->foreign("transaction_id")
                ->references("id")
                ->on("pr_transactions");
            $table->string("item_id")->nullable();
            $table->string("item_code")->nullable();
            $table->string("item_name")->nullable();
            $table->integer("category_id")->nullable();
            $table->unsignedInteger("uom_id")->index();
            $table
                ->foreign("uom_id")
                ->references("id")
                ->on("uoms");
            $table->double("quantity");
            $table->string("remarks")->nullable();
            $table->string("attachment")->nullable();
            $table->string("buyer_id")->nullalble();
            $table->string("buyer_name")->nullable();
            $table->timestamp("po_at")->nullable();
            $table->string("purchase_order_id")->nullable();
            $table->string("type")->nullable();
            $table->integer("warehouse_id")->nullable();
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
        Schema::dropIfExists("pr_items");
    }
};
