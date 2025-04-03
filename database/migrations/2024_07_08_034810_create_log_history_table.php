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
        Schema::create("log_history", function (Blueprint $table) {
            $table->id();
            $table->longText("activity");
            $table->string("pr_id")->nullable();
            $table->string("po_id")->nullable();
            $table->string("rr_id")->nullable();
            $table->string("jo_id")->nullable();
            $table->string("jo_po_id")->nullable();
            $table->string("jo_rr_id")->nullable();
            $table->integer("action_by")->nullable();
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
        Schema::dropIfExists("log_history");
    }
};
