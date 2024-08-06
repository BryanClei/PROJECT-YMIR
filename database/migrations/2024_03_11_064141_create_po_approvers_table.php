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
        Schema::create("po_approvers", function (Blueprint $table) {
            $table->increments("id");
            $table->unsignedInteger("po_settings_id");
            $table
                ->foreign("po_settings_id")
                ->references("id")
                ->on("po_settings");
            $table->unsignedInteger("approver_id");
            $table
                ->foreign("approver_id")
                ->references("id")
                ->on("users");
            $table->string("approver_name");
            $table->string("from_price");
            $table->string("to_price");
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
        Schema::dropIfExists("po_approvers");
    }
};
