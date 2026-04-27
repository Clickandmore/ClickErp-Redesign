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
        Schema::table('fn_sale_items', function (Blueprint $table) {
            $table->double('tax_value')->after('discount_value')->default(0);
            $table->double('tax_per')->after('discount_value')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_sale_items', function (Blueprint $table) {
            //
        });
    }
};
