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
        Schema::create("job_order_approvers", function (Blueprint $table) {
            $table->increments("id");
            $table->unsignedInteger("job_order_id")->index();
            $table
                ->foreign("job_order_id")
                ->references("id")
                ->on("job_order");
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
        Schema::dropIfExists("job_order_approvers");
    }
};
