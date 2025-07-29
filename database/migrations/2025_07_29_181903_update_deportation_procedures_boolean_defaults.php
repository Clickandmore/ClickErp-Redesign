<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateDeportationProceduresBooleanDefaults extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deportation_procedures', function (Blueprint $table) {
            // تغيير الحقول البوليانية لتقبل null كقيمة افتراضية
            $table->boolean('salary_received')->nullable()->default(null)->change();
            $table->boolean('has_passport')->nullable()->default(null)->change();
            $table->boolean('has_deportation_letter')->nullable()->default(null)->change();
            $table->boolean('has_settlement_session')->nullable()->default(null)->change();
            $table->boolean('has_final_exit')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deportation_procedures', function (Blueprint $table) {
            $table->boolean('salary_received')->default(false)->change();
            $table->boolean('has_passport')->default(false)->change();
            $table->boolean('has_deportation_letter')->default(false)->change();
            $table->boolean('has_settlement_session')->default(false)->change();
            $table->boolean('has_final_exit')->default(false)->change();
        });
    }
}
