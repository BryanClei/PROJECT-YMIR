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
        Schema::create("canvas_approver", function (Blueprint $table) {
            $table->increments("id");
            $table->unsignedInteger("approver_id");
            $table
                ->foreign("approver_id")
                ->references("id")
                ->on("users");
            $table->string("approver_name");
            $table->string("from_price");
            $table->string("to_price");
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
        Schema::dropIfExists("canvas_approver");
    }
};
