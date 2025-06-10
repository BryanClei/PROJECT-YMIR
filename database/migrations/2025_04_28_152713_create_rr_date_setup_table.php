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
        Schema::create("rr_date_setup", function (Blueprint $table) {
            $table->increments("id");
            $table->string("setup_name");
            $table->string("previous_days");
            $table->string("forward_days");
            $table->string("previous_month");
            $table->string("threshold_days");
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
        Schema::dropIfExists("rr_date_setup");
    }
};
