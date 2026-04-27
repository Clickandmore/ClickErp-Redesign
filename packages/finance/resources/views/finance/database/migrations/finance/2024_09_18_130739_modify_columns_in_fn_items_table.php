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
        Schema::table('fn_items', function (Blueprint $table) {
            $table->unsignedBigInteger('sale_unit_id')->nullable()->change();
            $table->unsignedBigInteger('purchase_unit_id')->nullable()->change();
            $table->string('details', 1000)->nullable()->change();
            $table->unsignedBigInteger('stock_account_id')->nullable()->change();
            $table->boolean('stock_tracking')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_items', function (Blueprint $table) {
            $table->unsignedBigInteger('sale_unit_id')->nullable(false)->change();
            $table->unsignedBigInteger('purchase_unit_id')->nullable(false)->change();
            $table->string('details', 1000)->nullable(false)->change();
            $table->unsignedBigInteger('stock_account_id')->nullable(false)->change();
            $table->boolean('stock_tracking')->nullable(false)->change();
        });
    }
};
