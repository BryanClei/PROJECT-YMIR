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
        Schema::create("warehouse_account_titles", function (Blueprint $table) {
            $table->increments("id");
            $table->unsignedInteger("warehouse_id");
            $table
                ->foreign("warehouse_id")
                ->references("id")
                ->on("warehouses")
                ->onDelete("cascade");

            $table->unsignedInteger("account_title_id");
            $table
                ->foreign("account_title_id")
                ->references("id")
                ->on("account_titles")
                ->onDelete("cascade");
            $table->string("transaction_type")->nullable();
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
        Schema::dropIfExists("warehouse_account_titles");
    }
};
