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
        Schema::table('fn_conversions', function (Blueprint $table) {
            $table->unsignedBigInteger('add_movement_id')->nullable()->after('stock_movement_id');
            $table->foreign('add_movement_id')->references('id')->on('fn_stock_movements')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_conversions', function (Blueprint $table) {
            $table->dropColumn('add_movement_id');
        });
    }
};
