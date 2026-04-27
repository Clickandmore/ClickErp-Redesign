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
        Schema::table('fn_purchases', function (Blueprint $table) {
            $table->string('notes', 1000)->default('-')->change();
        });
        Schema::table('fn_sales', function (Blueprint $table) {
            $table->string('notes', 1000)->default('-')->change();
        });
        Schema::table('fn_sales_orders', function (Blueprint $table) {
            $table->string('notes', 1000)->default('-')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_purchases', function (Blueprint $table) {
            //
        });
    }
};
