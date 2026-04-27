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
        Schema::table('fn_stock_settings', function (Blueprint $table) {
            $table->boolean('show_price_in_conversions')
            ->default(false)
            ->after('show_price_in_additional_permit')
            ->comment('اعداد ظهور السعر في التحويل بين الخزن');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_stock_settings', function (Blueprint $table) {
            $table->dropColumn('show_price_in_conversions');
        });
    }
};
