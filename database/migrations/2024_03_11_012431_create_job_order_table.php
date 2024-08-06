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
        Schema::create("job_order", function (Blueprint $table) {
            $table->increments("id");
            $table->string("module");
            $table->string("company_id");
            $table->string("business_unit_id");
            $table->string("department_id");
            $table->string("department_unit_id");
            $table->string("sub_unit_id");
            $table->string("location_id");
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
        Schema::dropIfExists("job_order");
    }
};
