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
        Schema::create("po_orders", function (Blueprint $table) {
            $table->increments("id");
            $table->unsignedInteger("pr_id")->index();
            $table
                ->foreign("pr_id")
                ->references("id")
                ->on("pr_transactions");
            $table->string("reference_no")->nullable();
            $table->string("pr_item_id");
            $table->unsignedInteger("po_id")->index();
            $table
                ->foreign("po_id")
                ->references("id")
                ->on("po_transactions");

            $table->string("item_id")->nullable();
            $table->string("item_code")->nullable();
            $table->string("item_name");

            $table->unsignedInteger("uom_id")->index();
            $table
                ->foreign("uom_id")
                ->references("id")
                ->on("uoms");

            $table->unsignedInteger("supplier_id")->index();
            $table
                ->foreign("supplier_id")
                ->references("id")
                ->on("suppliers")
                ->nullable();
            $table->string("attachment")->nullable();
            $table->string("buyer_id")->nullable();
            $table->string("buyer_name")->nullable();
            $table->double("price");
            $table->double("item_stock")->nullable();
            $table->double("quantity");
            $table->double("quantity_serve")->nullable();
            $table->double("total_price");
            $table->string("remarks")->nullable();
            $table->integer("warehouse_id")->nullable();
            $table->integer("category_id")->nullable();
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
        Schema::dropIfExists("po_orders");
    }
};
