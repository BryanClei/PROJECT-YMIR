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
        Schema::create("approver_settings", function (Blueprint $table) {
            $table->increments("id");
            $table->string("module");
            $table->string("company_id");
            $table->string("company_code");
            $table->string("business_unit_id");
            $table->string("business_unit_code");
            $table->string("department_id");
            $table->string("department_code");
            $table->string("department_unit_id");
            $table->string("department_unit_code");
            $table->string("sub_unit_id");
            $table->string("sub_unit_code");
            $table->string("location_id");
            $table->string("location_code");

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
        Schema::dropIfExists("approver_settings");
    }
};
