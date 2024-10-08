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
        Schema::create("asset_item", function (Blueprint $table) {
            $table->increments("id");
            $table->unsignedInteger("item_id")->index();
            $table
                ->foreign("item_id")
                ->references("id")
                ->on("items");

            $table->unsignedInteger("small_tools_id")->index();
            $table
                ->foreign("small_tools_id")
                ->references("id")
                ->on("small_tools");
            $table->string("code");
            $table->string("name");
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
        Schema::dropIfExists("asset_item");
    }
};
