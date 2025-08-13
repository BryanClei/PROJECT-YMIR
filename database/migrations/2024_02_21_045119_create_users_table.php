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
            $table
                ->unsignedInteger("one_charging_id")
                ->nullable()
                ->index();

            $table
                ->foreign("one_charging_id")
                ->references("id")
                ->on("one_charging");
            $table->string("one_charging_sync_id")->nullable();
            $table->string("one_charging_code")->nullable();
            $table->string("one_charging_name")->nullable();
            $table->string("company_id")->nullable();
            $table->string("company_code")->nullable();
            $table->string("business_unit_id")->nullable();
            $table->string("business_unit_code")->nullable();
            $table->string("department_id")->nullable();
            $table->string("department_code")->nullable();
            $table->string("department_unit_id")->nullable();
            $table->string("department_unit_code")->nullable();
            $table->string("sub_unit_id")->nullable();
            $table->string("sub_unit_code")->nullable();
            $table->string("location_id")->nullable();
            $table->string("location_code")->nullable();
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
