<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('fn_bonds', function (Blueprint $table) {
            $table->dropForeign(['tax_setting_id']); // Drop foreign key first
            $table->dropColumn(['tax_rate', 'tax_amount', 'tax_setting_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fn_bonds', function (Blueprint $table) {
            $table->double('tax_rate')->nullable()->after('is_tax');
            $table->double('tax_amount')->nullable()->after('tax_rate');
            $table->unsignedBigInteger('tax_setting_id')->nullable()->after('credit_account_id');
            $table->foreign('tax_setting_id')->references('id')->on('fn_tax_settings')->onDelete('cascade');
        });
    }
};
