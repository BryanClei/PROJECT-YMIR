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
        Schema::create("location_sub_units", function (Blueprint $table) {
            $table->increments("id");
            $table->unsignedInteger("location_id")->index();
            $table
                ->foreign("location_id")
                ->references("id")
                ->on("locations");

            $table->unsignedInteger("sub_unit_id")->index();
            $table
                ->foreign("sub_unit_id")
                ->references("id")
                ->on("sub_units");
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
        Schema::dropIfExists("location_sub_units");
    }
};
