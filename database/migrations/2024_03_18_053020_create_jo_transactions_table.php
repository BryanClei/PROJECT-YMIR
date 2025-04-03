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
        Schema::create("jo_transactions", function (Blueprint $table) {
            $table->increments("id");
            $table->string("jo_year_number_id");
            $table->integer("jo_number");
            $table->string("jo_description");
            $table->timestamp("date_needed");

            $table->unsignedInteger("user_id")->index();
            $table
                ->foreign("user_id")
                ->references("id")
                ->on("users");

            $table->unsignedInteger("type_id")->index();
            $table
                ->foreign("type_id")
                ->references("id")
                ->on("types");
            $table->string("type_name");

            $table->unsignedInteger("business_unit_id")->index();
            $table
                ->foreign("business_unit_id")
                ->references("id")
                ->on("business_units");
            $table->string("business_unit_name");

            $table->unsignedInteger("company_id")->index();
            $table
                ->foreign("company_id")
                ->references("id")
                ->on("companies");
            $table->string("company_name");

            $table->unsignedInteger("department_id")->index();
            $table
                ->foreign("department_id")
                ->references("id")
                ->on("departments");
            $table->string("department_name");

            $table->unsignedInteger("department_unit_id")->index();
            $table
                ->foreign("department_unit_id")
                ->references("id")
                ->on("department_units");
            $table->string("department_unit_name");

            $table->unsignedInteger("location_id")->index();
            $table
                ->foreign("location_id")
                ->references("id")
                ->on("locations");
            $table->string("location_name");

            $table->unsignedInteger("sub_unit_id")->index();
            $table
                ->foreign("sub_unit_id")
                ->references("id")
                ->on("sub_units");
            $table->string("sub_unit_name");

            $table->unsignedInteger("account_title_id")->index();
            $table
                ->foreign("account_title_id")
                ->references("id")
                ->on("account_titles");
            $table->string("account_title_name");

            $table->string("assets")->nullable();
            $table->string("module_name");
            $table->string("layer");
            $table->string("status")->nullable();
            $table->string("description")->nullable();
            $table->string("reason")->nullable();
            $table->string("for_po_only")->nullable();
            $table->string("for_po_only_id")->nullable();
            $table->string("direct_po")->nullable();
            $table->string("rush")->nullable();
            $table->string("outside_labor")->nullable();
            $table->string("cap_ex")->nullable();
            $table->string("helpdesk_id")->nullable();
            $table->string("cip_number")->nullable();
            $table->timestamp("approved_at")->nullable();
            $table->timestamp("rejected_at")->nullable();
            $table->timestamp("voided_at")->nullable();
            $table->timestamp("cancelled_at")->nullable();
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
        Schema::dropIfExists("jo_transactions");
    }
};
