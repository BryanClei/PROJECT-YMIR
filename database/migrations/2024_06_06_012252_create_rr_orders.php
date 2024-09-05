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
        Schema::create("rr_orders", function (Blueprint $table) {
            $table->increments("id");
            $table->string("rr_number");
            $table->unsignedInteger("rr_id")->index();
            $table
                ->foreign("rr_id")
                ->references("id")
                ->on("rr_transactions");
            $table->string("item_id")->nullable();
            $table->string("item_code")->nullable();
            $table->string("item_name")->nullable();
            $table->string("quantity_receive");
            $table->string("remaining");
            $table->string("shipment_no");
            $table->timestamp("delivery_date");
            $table->timestamp("rr_date");
            $table->string("attachment")->nullable();
            $table->integer("sync");
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
        Schema::dropIfExists("rr_orders");
    }
};
