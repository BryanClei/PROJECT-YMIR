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
        Schema::create("item_warehouse", function (Blueprint $table) {
            $table->increments("id");
            $table->unsignedInteger("item_id")->index();
            $table
                ->foreign("item_id")
                ->references("id")
                ->on("items");
            $table->unsignedInteger("warehouse_id")->index();
            $table
                ->foreign("warehouse_id")
                ->references("id")
                ->on("warehouses");
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("item_warehouse");
    }
};
