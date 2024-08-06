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
        Schema::create("jo_rr_order", function (Blueprint $table) {
            $table->increments("id");
            $table->string("jo_rr_number");
            $table->unsignedInteger("jo_rr_id")->index();
            $table
                ->foreign("jo_rr_id")
                ->references("id")
                ->on("jo_rr_transactions");
            $table->string("jo_item_id");
            $table->string("quantity_receive");
            $table->string("remaining");
            $table->string("shipment_no");
            $table->timestamp("delivery_date");
            $table->timestamp("rr_date");
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
        Schema::dropIfExists("jo_rr_order");
    }
};
