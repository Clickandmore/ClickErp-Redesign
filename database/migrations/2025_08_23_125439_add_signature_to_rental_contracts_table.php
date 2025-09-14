<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSignatureToRentalContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            $table->string("signature")->nullable()->after("status")->comment("مسار ملف التوقيع");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            $table->string("signature")->nullable()->after("status")->comment("مسار ملف التوقيع");
        });
    }
}
