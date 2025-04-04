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
        Schema::create("suppliers", function (Blueprint $table) {
            $table->increments("id");
            $table->string("name");
            $table->string("code");
            $table->string("type")->nullable();
            $table->double("term");
            $table->string("address_1")->nullable();
            $table->string("address_2")->nullable();
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
        Schema::dropIfExists("suppliers");
    }
};
