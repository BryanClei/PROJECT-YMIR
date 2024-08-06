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
        Schema::create("account_titles", function (Blueprint $table) {
            $table->increments("id");
            $table->string("name");
            $table->string("code");

            $table->unsignedInteger("account_type_id")->index();
            $table
                ->foreign("account_type_id")
                ->references("id")
                ->on("account_types")
                ->nullable();

            $table->unsignedInteger("account_group_id")->index();
            $table
                ->foreign("account_group_id")
                ->references("id")
                ->on("account_groups")
                ->nullable();

            $table->unsignedInteger("account_sub_group_id")->index();
            $table
                ->foreign("account_sub_group_id")
                ->references("id")
                ->on("account_sub_groups")
                ->nullable();

            $table->unsignedInteger("financial_statement_id")->index();
            $table
                ->foreign("financial_statement_id")
                ->references("id")
                ->on("account_financial_statement")
                ->nullable();

            $table->unsignedInteger("normal_balance_id")->index();
            $table
                ->foreign("normal_balance_id")
                ->references("id")
                ->on("account_normal_balance")
                ->nullable();

            $table->unsignedInteger("account_title_unit_id")->index();
            $table
                ->foreign("account_title_unit_id")
                ->references("id")
                ->on("account_title_units")
                ->nullable();

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
        Schema::dropIfExists("account_titles");
    }
};
