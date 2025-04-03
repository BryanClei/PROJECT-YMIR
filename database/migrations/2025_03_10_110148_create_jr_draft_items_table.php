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
        Schema::create("jr_draft_items", function (Blueprint $table) {
            $table->increments("id");
            $table->unsignedInteger("jr_draft_id")->index();
            $table
                ->foreign("jr_draft_id")
                ->references("id")
                ->on("jr_drafts");
            $table->string("description")->nullable();
            $table
                ->unsignedInteger("uom_id")
                ->index()
                ->nullable();
            $table
                ->foreign("uom_id")
                ->references("id")
                ->on("uoms");
            $table->double("quantity")->nullable();
            $table->double("unit_price")->nullable();
            $table->double("total_price")->nullable();
            $table->string("remarks")->nullable();
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
        Schema::dropIfExists("jr_draft_items");
    }
};
