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

            $table->dropForeign(['stock_id']);

            // Then, drop the 'stock_id' column
            $table->dropColumn('stock_id');

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
