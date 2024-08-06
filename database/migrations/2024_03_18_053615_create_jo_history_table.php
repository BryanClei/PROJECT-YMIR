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
        Schema::create("jo_history", function (Blueprint $table) {
            $table->increments("id");
            $table->unsignedInteger("jo_id")->index();
            $table
                ->foreign("jo_id")
                ->references("id")
                ->on("jo_transactions");
            $table->string("jo_po_id")->nullable();
            $table->string("approver_id");
            $table->string("approver_name");
            $table->timestamp("approved_at")->nullable();
            $table->timestamp("rejected_at")->nullable();
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
        Schema::dropIfExists("jo_history");
    }
};
