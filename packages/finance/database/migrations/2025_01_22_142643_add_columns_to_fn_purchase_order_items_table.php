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
        Schema::table('fn_purchase_order_items', function (Blueprint $table) {
            $table->double('discount_per')->default(0)->after('amount');
            $table->double('discount_value')->default(0)->after('discount_per');
            $table->double('tax_per')->default(0)->after('discount_value');
            $table->double('tax_value')->default(0)->after('tax_per');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_purchase_order_items', function (Blueprint $table) {
            $table->dropColumn('discount_per');
            $table->dropColumn('discount_value');
            $table->dropColumn('tax_per');
            $table->dropColumn('tax_value');
        });
    }
};
