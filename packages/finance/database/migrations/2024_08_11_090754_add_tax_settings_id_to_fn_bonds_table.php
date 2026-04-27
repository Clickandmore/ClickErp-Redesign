<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fn_bonds', function (Blueprint $table) {
            $table->unsignedBigInteger('tax_setting_id')->nullable()->after('credit_account_id');
            $table->foreign('tax_setting_id')->references('id')->on('fn_tax_settings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_bonds', function (Blueprint $table) {
            $table->dropColumn('tax_setting_id');
        });
    }
};
