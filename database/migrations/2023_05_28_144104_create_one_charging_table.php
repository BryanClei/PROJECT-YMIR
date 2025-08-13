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
        Schema::create("one_charging", function (Blueprint $table) {
            $table->increments("id");
            $table->string("sync_id")->unique();
            $table->string("code")->unique();
            $table->string("name");
            $table->string("company_id");
            $table->string("company_code");
            $table->string("company_name");
            $table->string("business_unit_id");
            $table->string("business_unit_code");
            $table->string("business_unit_name");
            $table->string("department_id");
            $table->string("department_code");
            $table->string("department_name");
            $table->string("department_unit_id");
            $table->string("department_unit_code");
            $table->string("department_unit_name");
            $table->string("sub_unit_id");
            $table->string("sub_unit_code");
            $table->string("sub_unit_name");
            $table->string("location_id");
            $table->string("location_code");
            $table->string("location_name");
            $table->softDeletes();
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
        Schema::dropIfExists("one_charging");
    }
};
