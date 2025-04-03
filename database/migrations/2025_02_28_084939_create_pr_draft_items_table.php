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
        Schema::create("pr_draft_items", function (Blueprint $table) {
            $table->increments("id");
            $table->unsignedInteger("pr_draft_id")->index();
            $table
                ->foreign("pr_draft_id")
                ->references("id")
                ->on("pr_drafts");
            $table->string("item_id")->nullable();
            $table->string("item_code")->nullable();
            $table->string("item_name")->nullable();
            $table->integer("category_id")->nullable();
            $table->unsignedInteger("uom_id")->index();
            $table
                ->foreign("uom_id")
                ->references("id")
                ->on("uoms")
                ->nullable();
            $table->double("item_stock")->nullable();
            $table->double("unit_price")->nullable();
            $table->double("total_price")->nullable();
            $table->double("quantity");
            $table->string("remarks")->nullable();
            $table->integer("supplier_id")->nullable();
            $table->integer("warehouse_id")->nullable();
            $table->string("assets")->nullable();
            $table->string("asset_code")->nullable();
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
        Schema::dropIfExists("pr_draft_items");
    }
};
