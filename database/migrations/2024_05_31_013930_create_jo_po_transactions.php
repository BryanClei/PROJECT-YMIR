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
        Schema::create("jo_po_transactions", function (Blueprint $table) {
            $table->increments("id");
            $table->integer("jo_number");
            $table->integer("po_number");
            $table->string("po_description");
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
            $table->unsignedInteger("supplier_id")->index();
            $table
                ->foreign("supplier_id")
                ->references("id")
                ->on("suppliers");
            $table->string("supplier_name");
            $table->string("module_name");
            $table->string("layer");
            $table->string("status")->nullable();
            $table->string("description")->nullable();
            $table->string("reason")->nullable();
            $table->string("edit_remarks")->nullable();
            $table->string("asset")->nullable();
            $table->string("sgp")->nullable();
            $table->string("f1")->nullable();
            $table->string("f2")->nullable();
            $table->timestamp("approved_at")->nullable();
            $table->timestamp("rejected_at")->nullable();
            $table->timestamp("voided_at")->nullable();
            $table->timestamp("cancelled_at")->nullable();
            $table->string("updated_by")->nullable();
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
        Schema::dropIfExists("jo_po_transactions");
    }
};
