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
        Schema::create("pr_drafts", function (Blueprint $table) {
            $table->increments("id");
            $table->integer("pr_draft_id");
            $table->longText("pr_description")->nullable();
            $table->timestamp("date_needed")->nullable();

            $table
                ->unsignedInteger("user_id")
                ->index()
                ->nullable();
            $table
                ->foreign("user_id")
                ->references("id")
                ->on("users");

            $table
                ->unsignedInteger("type_id")
                ->index()
                ->nullable();
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
            $table
                ->unsignedInteger("account_title_id")
                ->index()
                ->nullable();
            $table
                ->foreign("account_title_id")
                ->references("id")
                ->on("account_titles");
            $table->string("account_title_name")->nullable();
            $table->string("supplier_id")->nullable();
            $table->string("supplier_name")->nullable();
            $table->string("module_name");
            $table->string("cap_ex")->nullable();
            $table->string("status")->nullable();
            $table->string("asset_code")->nullable();
            $table->string("asset")->nullable();
            $table->string("sgp")->nullable();
            $table->string("f1")->nullable();
            $table->string("f2")->nullable();
            $table->string("rush")->nullable();
            $table->string("pcf_remarks")->nullable();
            $table->string("ship_to_id")->nullable();
            $table->string("ship_to_name")->nullable();
            $table->string("place_order")->nullable();
            $table->string("for_po_only")->nullable();
            $table->string("for_po_only_id")->nullable();
            $table->string("for_marketing")->nullable();
            $table->string("helpdesk_id")->nullable();
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
        Schema::dropIfExists("pr_drafts");
    }
};
