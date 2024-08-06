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
        Schema::create("sub_units", function (Blueprint $table) {
            $table->increments("id");
            $table->string("name");
            $table->string("code");
            $table->unsignedInteger("department_unit_id")->index();
            $table
                ->foreign("department_unit_id")
                ->references("id")
                ->on("department_units");

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
        Schema::dropIfExists("sub_units");
    }
};
