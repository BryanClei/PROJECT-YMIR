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
        Schema::create("rr_transactions", function (Blueprint $table) {
            $table->increments("id");
            $table->string("rr_year_number_id");
            $table->unsignedInteger("po_id")->index();
            $table
                ->foreign("po_id")
                ->references("id")
                ->on("po_transactions");
            $table->string("pr_id");
            $table->unsignedInteger("received_by")->index();
            $table
                ->foreign("received_by")
                ->references("id")
                ->on("users");
            $table->string("tagging_id");
            $table->timestamp("transaction_date")->nullable();
            $table->string("attachment")->nullable();
            $table->string("late_attachment")->nullable();
            $table->string("reason")->nullable();
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
        Schema::dropIfExists("rr_transactions");
    }
};
