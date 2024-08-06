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
        Schema::create("users", function (Blueprint $table) {
            $table->increments("id");
            $table->string("prefix_id");
            $table->string("id_number");
            $table->string("first_name");
            $table->string("middle_name")->nullable();
            $table->string("last_name");
            $table->string("suffix")->nullable();
            $table->string("position_name");
            $table->string("mobile_no")->nullable();
            $table->unsignedInteger("company_id")->index();
            $table
                ->foreign("company_id")
                ->references("id")
                ->on("companies");
            $table->unsignedInteger("business_unit_id")->index();
            $table
                ->foreign("business_unit_id")
                ->references("id")
                ->on("business_units");
            $table->unsignedInteger("department_id")->index();
            $table
                ->foreign("department_id")
                ->references("id")
                ->on("departments");
            $table->unsignedInteger("department_unit_id")->index();
            $table
                ->foreign("department_unit_id")
                ->references("id")
                ->on("department_units");
            $table->unsignedInteger("sub_unit_id")->index();
            $table
                ->foreign("sub_unit_id")
                ->references("id")
                ->on("sub_units");
            $table->unsignedInteger("location_id")->index();
            $table
                ->foreign("location_id")
                ->references("id")
                ->on("locations");
            $table->unsignedInteger("warehouse_id")->index();
            $table
                ->foreign("warehouse_id")
                ->references("id")
                ->on("warehouses");
            $table->string("username")->unique();
            $table->string("password");
            $table->unsignedInteger("role_id")->index();
            $table
                ->foreign("role_id")
                ->references("id")
                ->on("roles");
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
        Schema::dropIfExists("users");
    }
};
