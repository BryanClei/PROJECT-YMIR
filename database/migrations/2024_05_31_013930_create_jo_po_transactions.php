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
            $table->string("po_year_number_id");
            $table->integer("jo_number");
            $table->integer("po_number");
            $table->longText("po_description");
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

            $table->string("business_unit_id");
            $table->string("business_unit_code");
            $table->string("business_unit_name");
            $table->string("company_id");
            $table->string("company_code");
            $table->string("company_name");
            $table->string("department_id");
            $table->string("department_code");
            $table->string("department_name");
            $table->string("department_unit_id");
            $table->string("department_unit_code");
            $table->string("department_unit_name");
            $table->string("location_id");
            $table->string("location_code");
            $table->string("location_name");
            $table->string("sub_unit_id");
            $table->string("sub_unit_code");
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
                ->on("suppliers")
                ->nullable();
            $table->string("supplier_name")->nullable();
            $table->string("total_item_price")->nullable();
            $table->string("module_name");
            $table->string("layer");
            $table->string("status")->nullable();
            $table->longText("description")->nullable();
            $table->string("reason")->nullable();
            $table->string("edit_remarks")->nullable();
            $table->string("asset")->nullable();
            $table->string("sgp")->nullable();
            $table->string("f1")->nullable();
            $table->string("f2")->nullable();
            $table->string("direct_po")->nullable();
            $table->string("rush")->nullable();
            $table->string("pcf_remarks")->nullable();
            $table->string("ship_to_id")->nullable();
            $table->string("ship_to_name")->nullable();
            $table->string("outside_labor")->nullable();
            $table->string("cap_ex")->nullable();
            $table->string("helpdesk_id")->nullable();
            $table->string("cip_number")->nullable();
            $table->string("approver_remarks")->nullable();
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
