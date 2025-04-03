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
        Schema::create("set_approvers", function (Blueprint $table) {
            $table->increments("id");
            $table->unsignedInteger("approver_settings_id")->index();
            $table
                ->foreign("approver_settings_id")
                ->references("id")
                ->on("approver_settings");
            $table->string("approver_id");
            $table->string("approver_name");
            $table->string("layer");
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
        Schema::dropIfExists("set_approvers");
    }
};
