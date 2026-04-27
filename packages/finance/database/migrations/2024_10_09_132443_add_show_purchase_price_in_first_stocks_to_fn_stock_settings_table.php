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
            $table->boolean('show_purchase_price_in_first_stocks')
                ->default(false)
                ->comment('ظهور سعر الشراء في رصيد اول المدة');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_stock_settings', function (Blueprint $table) {
            //
        });
    }
};
