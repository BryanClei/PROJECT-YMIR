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
        Schema::create("masters_log_history", function (Blueprint $table) {
            $table->id();
            $table->string("module_type");
            $table->string("module_name");
            $table->string("action");
            $table->string("action_by");
            $table->string("action_by_name");
            $table->text("log_info");
            $table->json("previous_data");
            $table->json("new_data")->nullable();
            $table->string("ip_address")->nullable();
            $table->string("user_agent")->nullable();
            $table->timestamps();

            $table->index(["module_type", "module_name", "action"]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("masters_log_history");
    }
};
