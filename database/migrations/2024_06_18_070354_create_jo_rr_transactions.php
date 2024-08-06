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
        Schema::create("jo_rr_transactions", function (Blueprint $table) {
            $table->increments("id");
            $table->unsignedInteger("jo_po_id")->index();
            $table
                ->foreign("jo_po_id")
                ->references("id")
                ->on("jo_po_transactions");
            $table->unsignedInteger("jo_id")->index();
            $table
                ->foreign("jo_id")
                ->references("id")
                ->on("jo_transactions");
            $table->unsignedInteger("received_by")->index();
            $table
                ->foreign("received_by")
                ->references("id")
                ->on("users");
            $table->string("tagging_id");
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
        Schema::dropIfExists("jo_rr_transactions");
    }
};
